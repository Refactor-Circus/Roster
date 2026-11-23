<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use Workbench\App\Models\User;

/**
 * Acme and Globex, each with its own role, and an admin of Acme only, on
 * Roster's role tables.
 *
 * @return array{acme: OrganizationModel, globex: OrganizationModel, admin: User}
 */
function twoOrganizationsWithRoles(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    RoleModel::factory()->create(['scope' => 'organization', 'organization_id' => $acme->id, 'name' => 'Acme billing', 'slug' => 'acme-billing']);
    RoleModel::factory()->create(['scope' => 'organization', 'organization_id' => $globex->id, 'name' => 'Globex billing', 'slug' => 'globex-billing']);

    $admin = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $admin->getRouteKey()));
    grant($admin, 'admin', $acme);

    return ['acme' => $acme, 'globex' => $globex, 'admin' => $admin];
}

it('finds the organizations a user holds a permission in', function (): void {
    ['acme' => $acme, 'admin' => $admin] = twoOrganizationsWithRoles();
    $owner = user();
    $owned = organization($owner, ['name' => 'Owned']);
    $super = user();
    grant($super, 'super-admin');

    expect(app(Permissions::class)->organizationsWith($admin, 'roster.audit.view'))->toBe([$acme->id])
        ->and(app(Permissions::class)->organizationsWith($admin, 'roster.users.view'))->toBe([])
        ->and(app(Permissions::class)->organizationsWith($owner, 'roster.audit.view'))->toBe([$owned->id])
        // Owning an organization never grants a global-only permission.
        ->and(app(Permissions::class)->organizationsWith($owner, 'roster.users.view'))->toBe([])
        ->and(app(Permissions::class)->organizationsWith($super, 'roster.audit.view'))->toBeNull();
});

it('still refuses an organization the admin may not see', function (): void {
    ['admin' => $admin] = twoOrganizationsWithRoles();
    $this->actingAs($admin);

    $this->getJson(route('roster.roles.index', ['organization' => 'globex']))->assertForbidden();
});

it('lists shared roles and their organizations own roles', function (): void {
    ['admin' => $admin] = twoOrganizationsWithRoles();
    $this->actingAs($admin);

    $names = collect($this->getJson(route('roster.roles.index'))->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Acme billing', 'Member')->not->toContain('Globex billing');
});
