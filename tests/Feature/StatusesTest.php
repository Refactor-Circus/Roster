<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Enums\OrganizationStatus;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\ListTeamsAction;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Data\ListTeamsData;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Enums\TeamStatus;
use RefactorCircus\Roster\Domains\User\Actions\ApproveUserAction;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\DeactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Tests\Fixtures\Statuses\CustomerStatus;
use RefactorCircus\Roster\Tests\Fixtures\Statuses\IntegerStatus;
use RefactorCircus\Roster\Tests\Fixtures\Statuses\MemberStatus;

describe('organizations', function (): void {
    it('start with the default status and take a given one', function (): void {
        $organization = organization();

        expect($organization->refresh()->status)->toBe(OrganizationStatus::Active)
            ->and($organization->status_changed_at)->toBeNull();

        $inactive = app(CreateOrganizationAction::class)->execute(CreateOrganizationData::from(['name' => 'Initech', 'status' => 'inactive', 'status_reason' => 'Closed down']));

        expect($inactive->status)->toBe(OrganizationStatus::Inactive)
            ->and($inactive->status_reason)->toBe('Closed down')
            ->and($inactive->status_changed_at)->not->toBeNull();
    });

    it('stamps a status change and keeps the reason until it is changed', function (): void {
        $organization = organization();

        $organization = app(UpdateOrganizationAction::class)->execute($organization, UpdateOrganizationData::from(['status' => 'inactive', 'status_reason' => 'Blocked'], $organization));
        $changedAt = $organization->status_changed_at;

        expect($organization->status)->toBe(OrganizationStatus::Inactive)
            ->and($organization->status_reason)->toBe('Blocked')
            ->and($changedAt)->not->toBeNull();

        $this->travel(1)->minutes();

        $organization = app(UpdateOrganizationAction::class)->execute($organization, UpdateOrganizationData::from(['name' => 'Renamed', 'status' => 'inactive'], $organization));

        expect($organization->status_reason)->toBe('Blocked')
            ->and($organization->status_changed_at?->equalTo($changedAt))->toBeTrue();
    });

    it('rejects a status the enum does not have', function (): void {
        UpdateOrganizationData::from(['status' => 'archived']);
    })->throws(ValidationException::class, 'status');

    it('syncs the status of an external record', function (): void {
        $sync = fn (array $record) => app(SyncOrganizationAction::class)->execute(SyncOrganizationData::from(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech'] + $record));

        $created = $sync(['status' => 'inactive', 'status_reason' => 'Blocked in the ERP']);

        expect($created->outcome)->toBe('created')
            ->and($created->organization->status)->toBe(OrganizationStatus::Inactive)
            ->and($created->organization->status_reason)->toBe('Blocked in the ERP');

        expect($sync(['status' => 'inactive', 'status_reason' => 'Blocked in the ERP'])->outcome)->toBe('unchanged');

        $reactivated = $sync(['status' => 'active', 'status_reason' => null]);

        expect($reactivated->outcome)->toBe('updated')
            ->and($reactivated->organization->status)->toBe(OrganizationStatus::Active)
            ->and($reactivated->organization->status_reason)->toBeNull();
    });

    it('previews a status change without writing it', function (): void {
        app(SyncOrganizationAction::class)->execute(SyncOrganizationData::from(['source' => 'erp', 'external_id' => 'C-1', 'name' => 'Initech']));

        $outcome = app(SyncOrganizationAction::class)->preview(SyncOrganizationData::from(['source' => 'erp', 'external_id' => 'C-1', 'status' => 'inactive']));

        expect($outcome)->toBe('updated')
            ->and(OrganizationModel::query()->sole()->status)->toBe(OrganizationStatus::Active);
    });

    it('lists organizations by status', function (): void {
        organization(attributes: ['name' => 'Acme']);
        organization(attributes: ['name' => 'Initech', 'status' => 'inactive']);

        $inactive = app(ListOrganizationsAction::class)->execute(ListOrganizationsData::from(['status' => 'inactive']));

        expect($inactive->pluck('name')->all())->toBe(['Initech']);
    });

    it('shows and updates the status over HTTP', function (): void {
        organization(attributes: ['name' => 'Acme']);

        $this->patchJson(route('roster.organizations.update', 'acme'), ['status' => 'inactive', 'status_reason' => 'Unpaid'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive')
            ->assertJsonPath('data.status_reason', 'Unpaid');

        $this->getJson(route('roster.organizations.index', ['status' => 'active']))->assertOk()->assertJsonPath('meta.total', 0);
        $this->getJson(route('roster.organizations.index', ['status' => 'inactive']))->assertOk()->assertJsonPath('meta.total', 1);
    });
});

describe('teams', function (): void {
    it('start with the default status, change it and list by it', function (): void {
        $organization = organization();
        $team = app(CreateTeamAction::class)->execute($organization, CreateTeamData::from(['name' => 'Ops']));

        expect($team->status)->toBe(TeamStatus::Active);

        $team = app(UpdateTeamAction::class)->execute($team, UpdateTeamData::from(['status' => 'inactive', 'status_reason' => 'Merged'], $team));

        expect($team->status)->toBe(TeamStatus::Inactive)
            ->and($team->status_reason)->toBe('Merged')
            ->and($team->status_changed_at)->not->toBeNull();

        app(CreateTeamAction::class)->execute($organization, CreateTeamData::from(['name' => 'Sales']));

        expect(app(ListTeamsAction::class)->execute($organization, ListTeamsData::from(['status' => 'active']))->pluck('name')->all())->toBe(['Sales'])
            ->and(app(ListTeamsAction::class)->execute($organization, ListTeamsData::from(['status' => 'inactive']))->pluck('name')->all())->toBe(['Ops']);
    });

    it('shows, updates and filters by the status over HTTP', function (): void {
        $organization = organization(attributes: ['name' => 'Acme']);
        app(CreateTeamAction::class)->execute($organization, CreateTeamData::from(['name' => 'Ops']));

        $this->patchJson(route('roster.organizations.teams.update', ['acme', 'ops']), ['status' => 'inactive'])
            ->assertOk()
            ->assertJsonPath('data.status', 'inactive');

        $this->getJson(route('roster.organizations.teams.show', ['acme', 'ops']))->assertOk()->assertJsonPath('data.status', 'inactive');
        $this->getJson(route('roster.organizations.teams.index', ['acme', 'status' => 'active']))->assertOk()->assertJsonPath('meta.total', 0);
    });
});

describe('configured enums', function (): void {
    it('use an app enum for organizations', function (): void {
        config(['roster.statuses.organizations' => CustomerStatus::class]);

        $organization = organization();

        expect($organization->refresh()->status)->toBe(CustomerStatus::Prospect);

        $organization = app(UpdateOrganizationAction::class)->execute($organization, UpdateOrganizationData::from(['status' => 'blocked'], $organization));

        expect($organization->status)->toBe(CustomerStatus::Blocked)
            ->and(Statuses::values(Statuses::organizations()))->toBe(['prospect', 'customer', 'blocked']);

        UpdateOrganizationData::from(['status' => 'inactive']);
    })->throws(ValidationException::class, 'status');

    it('run the user flows through the cases the app enum names', function (): void {
        config(['roster.statuses.users' => MemberStatus::class]);

        $applicant = app(CreateUserAction::class)->execute(CreateUserData::from(['name' => 'Ada', 'email' => 'ada@example.com', 'status' => 'applied']));

        expect($applicant->rosterStatus())->toBe(MemberStatus::Applied)
            ->and($applicant->isRosterActive())->toBeFalse();

        $member = app(ApproveUserAction::class)->execute($applicant);

        expect($member->rosterStatus())->toBe(MemberStatus::Member);
        expect(app(SuspendUserAction::class)->execute(user())->rosterStatus())->toBe(MemberStatus::OnHold)
            ->and(app(DeactivateUserAction::class)->execute(user())->rosterStatus())->toBe(MemberStatus::Closed)
            ->and(user()->rosterStatus())->toBe(MemberStatus::Member);
    });

    it('let in users whose status allows access', function (): void {
        config(['roster.statuses.users' => MemberStatus::class]);
        Route::middleware(['web', 'roster.active'])->get('roster-test/dashboard', fn (): string => 'ok');

        $trial = user();
        $trial->roster()->update(['status' => MemberStatus::Trial]);

        $this->actingAs($trial->refresh())->get('roster-test/dashboard')->assertOk();

        $onHold = app(SuspendUserAction::class)->execute(user());

        $this->actingAs($onHold)->get('roster-test/dashboard')->assertForbidden();
    });

    it('must be string-backed enums implementing the contract', function (string $enum): void {
        config(['roster.statuses.users' => $enum]);

        Statuses::users();
    })->with([
        'organization contract only' => CustomerStatus::class,
        'integer-backed' => IntegerStatus::class,
        'not an enum' => stdClass::class,
    ])->throws(InvalidArgumentException::class, 'roster.statuses.users');
});
