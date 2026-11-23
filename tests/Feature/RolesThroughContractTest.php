<?php

declare(strict_types=1);

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Authorization\NullRoles;
use RefactorCircus\Keystone\Testing\FakePermissions;
use RefactorCircus\Keystone\Testing\FakeRoles;
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\TransferOwnershipAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\TransferOwnershipData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Exporters\MembersExporter;
use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Domains\User\UserServiceProvider;
use RefactorCircus\Roster\Facades\Roster;
use RefactorCircus\Roster\Tests\Fixtures\PlainUser;
use Workbench\App\Models\User;

/**
 * Keystone's `FakePermissions` with a record of every `forget()`, and an
 * implementation that cannot list abilities when `$listsAbilities` is false.
 */
final class RecordingPermissions implements Permissions
{
    /** @var list<int|string|null> */
    public array $forgotten = [];

    public function __construct(
        public readonly FakePermissions $fake = new FakePermissions,
        public bool $listsAbilities = true,
    ) {}

    public function allows(?Authenticatable $user, string $ability, mixed $scope = null): bool
    {
        return $this->fake->allows($user, $ability, $scope);
    }

    public function scopesWith(?Authenticatable $user, string $ability, string $scopeType): ?array
    {
        return $this->fake->scopesWith($user, $ability, $scopeType);
    }

    public function abilities(?Authenticatable $user, mixed $scope = null): ?array
    {
        return $this->listsAbilities ? $this->fake->abilities($user, $scope) : null;
    }

    public function isSuperAdmin(?Authenticatable $user): bool
    {
        return $this->fake->isSuperAdmin($user);
    }

    public function forget(?Authenticatable $user = null): void
    {
        $identifier = $user?->getAuthIdentifier();

        $this->forgotten[] = is_int($identifier) || is_string($identifier) ? $identifier : null;
    }
}

/**
 * Claims every email, as a single sign-on package would for its domains.
 */
final class EveryoneSignsInExternally implements SignInRequirement
{
    public function for(string $email): ?ExternalSignIn
    {
        return new ExternalSignIn(key: 'idp', label: 'Corporate IdP', url: 'https://idp.test/start');
    }
}

beforeEach(function (): void {
    $this->permissions = new RecordingPermissions;
    app()->instance(Permissions::class, $this->permissions);

    $this->roles = FakeRoles::install()
        ->define('super-admin', [Permissions::SUPER_ADMIN])
        ->define('approver', ['roster.users.approve'])
        ->define('admin', ['roster.members.view', 'roster.members.manage', 'roster.teams.manage'], OrganizationModel::class)
        ->define('member', ['roster.members.view'], OrganizationModel::class)
        ->define('lead', ['roster.teams.manage'], TeamModel::class);

    $this->acme = organization(attributes: ['name' => 'Acme']);
    $this->ops = app(CreateTeamAction::class)->execute($this->acme, new CreateTeamData(name: 'Ops'));
    $this->ada = user(['name' => 'Ada', 'email' => 'ada@example.com']);
});

function joinAcme(User $user): void
{
    app(AddMemberAction::class)->execute(test()->acme, new AddMemberData(user: $user->getRouteKey()));
}

describe('joining and leaving', function (): void {
    it('assigns the default member role in the organization scope on joining', function (): void {
        $this->permissions->forgotten = [];

        joinAcme($this->ada);

        expect($this->roles->of($this->ada, $this->acme))->toBe(['member'])
            ->and($this->roles->of($this->ada))->toBe([])
            ->and($this->permissions->forgotten)->toContain($this->ada->getKey())
            // The owner needs no role.
            ->and($this->roles->of($this->acme->owner, $this->acme))->toBe([]);
    });

    it('assigns no role when the default member role is null or unknown, without failing', function (?string $slug): void {
        config()->set('roster.roles.default_member', $slug);

        joinAcme($this->ada);

        expect($this->acme->membershipFor($this->ada))->not->toBeNull()
            ->and($this->roles->of($this->ada, $this->acme))->toBe([]);
    })->with(['null' => [null], 'unknown' => ['ghost']]);

    it('revokes in the organization and every team the user sat on when they leave it', function (): void {
        $globex = organization(attributes: ['name' => 'Globex']);
        app(AddMemberAction::class)->execute($globex, new AddMemberData(user: $this->ada->getRouteKey()));
        joinAcme($this->ada);
        app(AddTeamMemberAction::class)->execute($this->ops, new AddTeamMemberData(user: $this->ada->getRouteKey()));
        $this->roles->assign($this->ada, 'admin', $this->acme);
        $this->roles->assign($this->ada, 'lead', $this->ops);
        $this->permissions->forgotten = [];

        app(RemoveMemberAction::class)->execute($this->acme, $this->ada);

        expect($this->roles->of($this->ada, $this->acme))->toBe([])
            ->and($this->roles->of($this->ada, $this->ops))->toBe([])
            // Other organizations keep theirs.
            ->and($this->roles->of($this->ada, $globex))->toBe(['member'])
            ->and($this->permissions->forgotten)->toContain($this->ada->getKey());
    });

    it('revokes in the team only when they leave a team', function (): void {
        joinAcme($this->ada);
        app(AddTeamMemberAction::class)->execute($this->ops, new AddTeamMemberData(user: $this->ada->getRouteKey()));
        $this->roles->assign($this->ada, 'lead', $this->ops);
        $this->permissions->forgotten = [];

        app(RemoveTeamMemberAction::class)->execute($this->ops, $this->ada);

        expect($this->roles->of($this->ada, $this->ops))->toBe([])
            ->and($this->roles->of($this->ada, $this->acme))->toBe(['member'])
            ->and($this->permissions->forgotten)->toContain($this->ada->getKey());
    });

    it('forgets both users when ownership changes hands', function (): void {
        $owner = $this->acme->owner;
        joinAcme($this->ada);
        $this->permissions->forgotten = [];

        app(TransferOwnershipAction::class)->execute($this->acme, new TransferOwnershipData(user: $this->ada->getRouteKey()));

        expect($this->permissions->forgotten)->toContain($this->ada->getKey())
            ->toContain($owner->getKey());
    });
});

describe('deleting', function (): void {
    beforeEach(function (): void {
        joinAcme($this->ada);
        $this->roles->assign($this->ada, 'approver');
        $this->roles->assign($this->ada, 'admin', $this->acme);
    });

    it('revokes every role when a user is deleted for good', function (): void {
        $this->permissions->forgotten = [];

        // A host model that does not soft-delete.
        app(DeleteUserAction::class)->execute(PlainUser::query()->findOrFail($this->ada->getKey()));

        expect($this->roles->of($this->ada))->toBe([])
            ->and($this->roles->of($this->ada, $this->acme))->toBe([])
            ->and($this->permissions->forgotten)->toContain($this->ada->getKey());
    });

    it('keeps the roles of a soft-deleted user, so a restore brings them back', function (): void {
        app(DeleteUserAction::class)->execute($this->ada);

        expect($this->roles->of($this->ada))->toBe(['approver'])
            ->and($this->roles->of($this->ada, $this->acme))->toBe(['admin', 'member']);
    });

    it('revokes every role when a user is purged', function (): void {
        app(DeleteUserAction::class)->execute($this->ada);
        $this->permissions->forgotten = [];

        app(PurgeUserAction::class)->execute($this->ada->refresh());

        expect($this->roles->of($this->ada))->toBe([])
            ->and($this->roles->of($this->ada, $this->acme))->toBe([])
            ->and($this->permissions->forgotten)->toContain($this->ada->getKey());
    });
});

describe('approvals', function (): void {
    it('takes the approvers from the holders of roster.users.approve', function (): void {
        $boss = user(['email' => 'boss@example.com']);
        $carl = user(['email' => 'carl@example.com']);
        $this->roles->assign($this->ada, 'approver');
        $this->roles->assign($boss, 'super-admin');
        // An organization role is not a global grant.
        joinAcme($carl);
        $this->roles->assign($carl, 'admin', $this->acme);

        expect(app(Approvals::class)->approvers()->modelKeys())->toEqualCanonicalizing([$this->ada->getKey(), $boss->getKey()]);
    });

    it('notifies nobody, without failing, when nothing holds the ability', function (): void {
        app()->instance(Roles::class, new NullRoles);
        config()->set('roster.users.approvals.notify_approvers', true);
        Notification::fake();

        app(Approvals::class)->pending(user());

        expect(app(Approvals::class)->approvers())->toHaveCount(0);
        Notification::assertNothingSent();
    });
});

describe('transfers', function (): void {
    it('exports each member\'s organization roles from the role store', function (): void {
        joinAcme($this->ada);
        $this->roles->assign($this->ada, 'admin', $this->acme);
        $this->roles->assign($this->ada, 'lead', $this->ops);
        $transfer = TransferModel::factory()->create(['type' => 'export_members', 'organization_id' => $this->acme->getKey(), 'requested_by' => $this->ada->getKey()]);

        $rows = collect(app(MembersExporter::class)->rows($transfer, null, 100))
            ->mapWithKeys(fn (array $row): array => [$row['cells'][0] => $row['cells'][5]]);

        expect($rows->get('ada@example.com'))->toBe('admin;member')
            ->and($rows->get($this->acme->owner->email))->toBe('');
    });

    it('refuses an unknown import role and assigns a known one through the role store', function (): void {
        joinAcme($this->ada);
        $owner = $this->acme->owner;
        $transfer = app(StartImportAction::class)->execute(new StartImportData(
            type: TransferType::ImportMembers,
            organization: 'acme',
            content: "email,name,teams,role\nada@example.com,Ada,,admin\nnew@example.com,New,,ghost",
        ), $owner);

        $rows = $transfer->rows();

        expect($rows[0]['action'])->toBe('update')
            ->and($rows[1]['action'])->toBe('error')
            ->and($rows[1]['reasons'])->toBe([__('roster::roster.import_unknown_role', ['role' => 'ghost'])]);

        app(ConfirmImportAction::class)->execute($transfer, $owner);

        expect($this->roles->of($this->ada, $this->acme))->toBe(['admin', 'member']);
    });

    it('skips a member who already holds the imported role', function (): void {
        joinAcme($this->ada);

        $transfer = app(StartImportAction::class)->execute(new StartImportData(
            type: TransferType::ImportMembers,
            organization: 'acme',
            content: "email,name,teams,role\nada@example.com,Ada,,member",
        ), $this->acme->owner);

        expect($transfer->rows()[0]['action'])->toBe('skip');
    });

    it('lets a super-admin, the owner, or a holder of the role hand out import roles while authorization is on', function (): void {
        config()->set('roster.authorization', true);
        joinAcme($this->ada);
        $boss = user();
        $manager = user(['name' => 'Manager']);
        $holder = user(['name' => 'Holder']);
        joinAcme($manager);
        joinAcme($holder);
        $this->permissions->fake->superAdmin($boss);
        foreach ([$manager, $holder] as $actor) {
            $this->permissions->fake->grant($actor, 'roster.members.manage', $this->acme);
        }
        $this->roles->assign($holder, 'admin', $this->acme);
        $import = fn (User $actor): TransferModel => app(StartImportAction::class)->execute(new StartImportData(
            type: TransferType::ImportMembers,
            organization: 'acme',
            content: "email,name,teams,role\nada@example.com,Ada,,admin",
        ), $actor);

        expect($import($boss)->rows()[0]['action'])->toBe('update')
            ->and($import($this->acme->owner)->rows()[0]['action'])->toBe('update')
            ->and($import($holder)->rows()[0]['action'])->toBe('update')
            ->and($import($manager)->rows()[0]['reasons'])->toBe([__('roster::roster.import_role_not_grantable', ['role' => 'admin'])]);
    });
});

describe('impersonation', function (): void {
    beforeEach(function (): void {
        $this->actor = user(['name' => 'Actor']);
        $this->start = fn (?string $organization = null) => app(StartImpersonationAction::class)
            ->execute($this->ada, new StartImpersonationData(reason: 'Ticket 1', organization: $organization), $this->actor);
    });

    it('keeps super-admins from being impersonated by anyone but a super-admin', function (): void {
        $this->permissions->fake->superAdmin($this->ada);

        expect(fn () => ($this->start)())->toThrow(ValidationException::class, __('roster::roster.impersonate_super'));

        $this->permissions->fake->superAdmin($this->actor);

        expect(($this->start)()->impersonation->user_id)->toBe($this->ada->getKey());
    });

    it('refuses to hand the actor abilities they do not hold', function (): void {
        joinAcme($this->ada);
        joinAcme($this->actor);
        $this->roles->assign($this->ada, 'admin', $this->acme);

        expect(fn () => ($this->start)('acme'))->toThrow(ValidationException::class, __('roster::roster.impersonate_escalation'));

        $this->roles->assign($this->actor, 'admin', $this->acme);

        expect(($this->start)('acme')->impersonation->organization_id)->toBe($this->acme->getKey());
    });

    it('lets only a super-admin impersonate when abilities cannot be listed', function (): void {
        $this->permissions->listsAbilities = false;

        expect(fn () => ($this->start)())->toThrow(ValidationException::class, __('roster::roster.impersonate_escalation'));

        $this->permissions->fake->superAdmin($this->actor);

        expect(($this->start)()->impersonation->user_id)->toBe($this->ada->getKey());
    });
});

describe('reads', function (): void {
    it('passes a super-admin through external sign-in requirements', function (): void {
        $boss = user(['email' => 'boss@example.com']);
        $this->permissions->fake->superAdmin($boss);
        Roster::requireSignIn(new EveryoneSignsInExternally);

        expect(Roster::externalSignInFor('boss@example.com'))->toBeNull()
            ->and(Roster::externalSignInFor('ada@example.com')?->key)->toBe('idp');
    });

    it('answers hasRosterPermission() from the bound permissions', function (): void {
        $globex = organization(attributes: ['name' => 'Globex']);
        $this->permissions->fake->grant($this->ada, 'roster.teams.manage', $this->acme);

        expect($this->ada->hasRosterPermission('roster.teams.manage', $this->acme))->toBeTrue()
            ->and($this->ada->hasRosterPermission('roster.teams.manage', $this->ops))->toBeTrue()
            ->and($this->ada->hasRosterPermission('roster.teams.manage', $globex))->toBeFalse()
            ->and($this->ada->hasRosterPermission('roster.teams.manage'))->toBeFalse();
    });

    it('has no rosterRoleAssignments relation, on the trait or registered for a plain model', function (): void {
        config()->set('roster.users.model', PlainUser::class);
        $provider = new UserServiceProvider(app());
        (new ReflectionMethod($provider, 'registerProfileRelation'))->invoke($provider);
        $plain = PlainUser::query()->findOrFail($this->ada->getKey());

        expect(method_exists(User::class, 'rosterRoleAssignments'))->toBeFalse()
            ->and($plain->rosterMemberships())->not->toBeNull()
            ->and(fn () => $plain->rosterRoleAssignments())->toThrow(BadMethodCallException::class);
    });
});
