<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Keen\Domains\Audit\Models\AuditEntryModel;
use RefactorCircus\Keystone\Audit\History;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\TransferContext;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateUserAction;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Data\UpdateUserData;
use RefactorCircus\Roster\Facades\Roster;
use RefactorCircus\Roster\Mcp\Tools\ListRosterHistoryTool;
use RefactorCircus\Roster\Tests\Fixtures\Billing\InvoicePaidActionEvent;
use RefactorCircus\Roster\Tests\Fixtures\Billing\InvoicePayingActionEvent;

function keenEntry(string $action): AuditEntryModel
{
    return AuditEntryModel::query()->where('action', $action)->latest('id')->firstOrFail();
}

it('records a suspension with the user as subject, the organization as scope and the impersonator', function (): void {
    $admin = user(['name' => 'Admin']);
    grant($admin, 'super-admin');
    $ada = user(['name' => 'Ada']);
    grant($ada, 'super-admin');
    $grace = user(['name' => 'Grace']);
    $acme = organization($grace, ['name' => 'Acme']);

    $this->actingAs($admin);
    $started = app(StartImpersonationAction::class)->execute($ada, new StartImpersonationData(reason: 'Ticket 42'), $admin);
    $this->get($started->url);

    app(SuspendUserAction::class)->execute($grace, new SuspendUserData(reason: 'Spam'));

    $entry = keenEntry('user.suspended');

    expect($entry->source)->toBe('roster')
        ->and($entry->subject_type)->toBe($grace->getMorphClass())
        ->and($entry->subject_id)->toBe((string) $grace->getKey())
        ->and($entry->subject_label)->toBe('Grace')
        ->and($entry->scope_type)->toBe($acme->getMorphClass())
        ->and($entry->scope_id)->toBe((string) $acme->getKey())
        ->and($entry->actor_id)->toBe((string) $ada->getKey())
        ->and($entry->context['impersonator'])->toBe(['id' => (string) $admin->getKey(), 'label' => 'Admin'])
        ->and($entry->changes['profile.status'][1])->toBe('suspended');

    // The impersonation itself is about the person impersonated.
    expect(keenEntry('impersonation.started')->subject_label)->toBe('Ada');
});

it('records a role assignment about the user, with the role in the diff', function (): void {
    $ada = user(['name' => 'Ada']);
    $acme = organization($ada, ['name' => 'Acme']);
    $admin = RoleModel::query()->where('slug', 'admin')->sole();

    app(AssignRoleAction::class)->execute($ada, new AssignRoleData(role: $admin->id, organization: $acme->slug));

    $entry = keenEntry('role.assigned');

    expect($entry->subject_label)->toBe('Ada')
        ->and($entry->scope_id)->toBe((string) $acme->getKey());
});

it('diffs a role\'s permissions and a user\'s profile', function (): void {
    $role = roleWith(['roster.users.view']);
    app(UpdateRoleAction::class)->execute($role, new UpdateRoleData(permissions: ['roster.users.view', 'roster.users.update']));

    expect(keenEntry('role.updated')->changes['permissions'])->toBe([['roster.users.view'], ['roster.users.update', 'roster.users.view']]);

    $ada = user(['name' => 'Ada']);
    app(UpdateProfileAction::class)->execute($ada, new UpdateProfileData(displayName: 'Countess'));

    expect(keenEntry('profile.updated')->changes['profile.display_name'])->toBe([null, 'Countess']);
});

it('redacts passwords', function (): void {
    $ada = user(['name' => 'Ada']);
    app(UpdateUserAction::class)->execute($ada, new UpdateUserData(password: 'correct-horse-battery'));

    expect(keenEntry('user.updated')->changes['password'] ?? null)->toBe(['[redacted]', '[redacted]']);

    $log = json_encode(AuditEntryModel::query()->get()->toArray());

    expect($log)->not->toContain('correct-horse-battery');
});

it('adds no scim_token context to Roster\'s entries, but still its other context', function (): void {
    $admin = user(['name' => 'Admin']);
    grant($admin, 'super-admin');
    $ada = user(['name' => 'Ada']);
    grant($ada, 'super-admin');
    $grace = user(['name' => 'Grace']);

    $this->actingAs($admin);
    $started = app(StartImpersonationAction::class)->execute($ada, new StartImpersonationData(reason: 'Ticket 42'), $admin);
    $this->get($started->url);

    app(SuspendUserAction::class)->execute($grace, new SuspendUserData(reason: 'Spam'));

    $context = keenEntry('user.suspended')->context;

    // The positive control: the impersonator context lands on the same entry.
    expect($context)->toHaveKey('impersonator')
        ->and($context['impersonator'])->toBe(['id' => (string) $admin->getKey(), 'label' => 'Admin'])
        ->and($context)->not->toHaveKey('scim_token');
});

it('leaves an old entry\'s scim_token context as written, and still serves it', function (): void {
    app(SuspendUserAction::class)->execute(user(['name' => 'Grace']));
    $entry = keenEntry('user.suspended');

    // Written by an older Roster, whose SCIM domain added the token.
    DB::table($entry->getTable())->where('id', $entry->getKey())->update([
        'context' => json_encode([...(array) $entry->context, 'scim_token' => ['id' => 7, 'name' => 'Okta']]),
    ]);

    expect(keenEntry('user.suspended')->context['scim_token'] ?? null)->toBe(['id' => 7, 'name' => 'Okta']);

    $this->getJson(route('roster.history.index'))
        ->assertOk()
        ->assertJsonPath('data.0.action', 'user.suspended');

    grant($auditor = user(), 'super-admin');
    $this->actingAs($auditor);

    mcpTool(ListRosterHistoryTool::class)->assertOk()->assertSee('user.suspended');
});

it('records the import a change came from', function (): void {
    $transfer = TransferModel::factory()->create(['type' => TransferType::ImportUsers, 'requested_by' => user()->getKey()]);
    app(TransferContext::class)->transfer = $transfer;

    app(SuspendUserAction::class)->execute(user());

    expect(keenEntry('user.suspended')->context['transfer'])->toBe(['id' => $transfer->id, 'type' => 'import_users']);
});

it('keeps Roster out of the audit log when its auditing is turned off', function (): void {
    config()->set('roster.audit.enabled', false);

    app(SuspendUserAction::class)->execute(user());

    expect(AuditEntryModel::query()->where('source', 'roster')->count())->toBe(0)
        ->and(Roster::auditing())->toBeFalse();

    $this->actingAs($admin = user());
    grant($admin, 'super-admin');

    $this->getJson(route('roster.history.index'))->assertNotFound()->assertJsonPath('message', 'Auditing is turned off for this package.');
});

it('leaves the subject and scope of other packages\' events to the audit log', function (): void {
    app(PackageRegistry::class)->register(Package::make('billing', 'RefactorCircus\Roster\Tests\Fixtures\Billing'));
    $ada = user(['name' => 'Ada']);
    organization($ada);

    event(new InvoicePayingActionEvent($ada));
    event(new InvoicePaidActionEvent($ada));

    $entry = keenEntry('invoice.paid');

    expect($entry->source)->toBe('billing')
        ->and($entry->subject_label)->toBe('Ada')
        ->and($entry->scope_id)->toBeNull();
});

it('serves Roster\'s history once Keen is installed, behind roster.audit.view', function (): void {
    app(SuspendUserAction::class)->execute($grace = user(['name' => 'Grace']));

    $this->getJson(route('roster.history.index'))
        ->assertOk()
        ->assertJsonPath('data.0.action', 'user.suspended');

    config()->set('roster.authorization', true);

    $this->actingAs(user())->getJson(route('roster.history.index'))->assertForbidden();

    $auditor = user();
    grant($auditor, roleWith(['roster.audit.view'])->slug);

    expect(Gate::forUser($auditor)->allows(History::ABILITY, ['roster']))->toBeTrue()
        ->and(Gate::forUser($grace)->allows(History::ABILITY, ['roster']))->toBeFalse();
});

it('offers the history tool once Keen is installed, behind roster.audit.view', function (): void {
    app(SuspendUserAction::class)->execute(user());
    config()->set('roster.authorization', true);

    mcpTool(ListRosterHistoryTool::class)->assertHasErrors(['Unauthorized.']);

    $this->actingAs($viewer = user());
    mcpTool(ListRosterHistoryTool::class)->assertHasErrors(['Unauthorized.']);

    grant($viewer, roleWith(['roster.audit.view'])->slug);
    mcpTool(ListRosterHistoryTool::class)->assertOk()->assertSee('user.suspended');
});
