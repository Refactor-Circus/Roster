<?php

declare(strict_types=1);

require_once __DIR__.'/../../Feature/Scim/helpers.php';

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Tables;

it('migrates every table under its configured name', function (): void {
    foreach (array_keys(Tables::DEFAULTS) as $key) {
        if ($key === 'users') {
            continue;
        }

        expect(Schema::hasTable('crm_'.$key))->toBeTrue()
            ->and(Schema::hasTable('roster_'.$key))->toBeFalse();
    }
});

it('keeps organizations, teams, roles and permissions working on renamed tables', function (): void {
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada->getRouteKey()));

    $role = app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Auditor', scope: RoleScope::Organization, permissions: ['roster.members.view']));
    grant($ada, $role->slug, $acme);

    expect($acme->members()->count())->toBe(2)
        ->and($ops->hasMember($ada))->toBeTrue()
        ->and($ada->rosterOrganizations()->pluck('slug')->all())->toBe(['acme'])
        ->and(app(Permissions::class)->allows($ada, 'roster.members.view', $acme))->toBeTrue()
        ->and(app(Permissions::class)->allows($ada, 'roster.members.manage', $acme))->toBeFalse();

    // Validation rules look in the renamed tables too.
    expect(validator(['name' => 'Other', 'slug' => 'acme'], CreateOrganizationData::rules())->errors()->keys())->toBe(['slug'])
        ->and(validator(['name' => 'Ok', 'scope' => 'organization', 'permissions' => ['roster.members.view', 'nope']], CreateRoleData::rules())->errors()->keys())->toBe(['permissions.1'])
        ->and(validator(['name' => 'Ops', 'slug' => 'ops'], CreateTeamData::rules($acme))->fails())->toBeTrue();
});

it('serves SCIM groups from renamed tables', function (): void {
    [$this->acme, $this->scimToken] = scimOrg();
    $ada = (string) scim('POST', '/Users', oktaUser())->json('id');

    scim('POST', '/Groups', ['displayName' => 'Engineering', 'members' => [['value' => $ada]]])->assertCreated();

    scim('GET', '/Groups')->assertOk()->assertJsonPath('Resources.0.members.0.value', $ada);
    scim('GET', '/Users/'.$ada)->assertJsonPath('groups.0.display', 'Engineering');

    expect(TeamModel::query()->sole()->getTable())->toBe('crm_teams');
});
