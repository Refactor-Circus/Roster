<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ListOrganizationsTool;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use Workbench\App\Models\User;

/**
 * Acme and Globex, each with its own role, and an admin of Acme only.
 *
 * @return array{acme: OrganizationModel, globex: OrganizationModel, admin: User}
 */
function twoOrganizations(): array
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
    ['acme' => $acme, 'admin' => $admin] = twoOrganizations();
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

it('lists only their own organizations to organization admins, on every surface', function (): void {
    ['admin' => $admin] = twoOrganizations();
    $this->actingAs($admin);

    expect(collect($this->getJson(route('roster.organizations.index'))->assertOk()->json('data'))->pluck('slug')->all())->toBe(['acme']);

    mcpTool(ListOrganizationsTool::class)->assertOk()->assertSee('Acme')->assertDontSee('Globex');
});

it('still refuses an organization the admin may not see', function (): void {
    ['admin' => $admin] = twoOrganizations();
    $this->actingAs($admin);

    $this->getJson(route('roster.roles.index', ['organization' => 'globex']))->assertForbidden();
});

it('lists shared roles and their organizations own roles', function (): void {
    ['admin' => $admin] = twoOrganizations();
    $this->actingAs($admin);

    $names = collect($this->getJson(route('roster.roles.index'))->assertOk()->json('data'))->pluck('name');

    expect($names)->toContain('Acme billing', 'Member')->not->toContain('Globex billing');
});

it('lists their own transfers and their organizations transfers', function (): void {
    ['acme' => $acme, 'globex' => $globex, 'admin' => $admin] = twoOrganizations();

    $ownId = TransferModel::factory()->create(['type' => 'export_users', 'requested_by' => $admin->getKey(), 'organization_id' => null])->id;
    $acmeId = TransferModel::factory()->create(['type' => 'export_members', 'requested_by' => user()->getKey(), 'organization_id' => $acme->id])->id;
    $globexId = TransferModel::factory()->create(['type' => 'export_members', 'requested_by' => user()->getKey(), 'organization_id' => $globex->id])->id;

    $ids = collect($this->actingAs($admin)->getJson(route('roster.transfers.index'))->assertOk()->json('data'))->pluck('id')->all();

    expect($ids)->toContain($ownId, $acmeId)->not->toContain($globexId);
});

it('searches only the organizations the searcher may view', function (): void {
    ['admin' => $admin] = twoOrganizations();
    $this->actingAs($admin);

    $names = fn (string $query): array => collect($this->getJson(route('roster.search.organizations', ['q' => $query]))->assertOk()->json('data'))->pluck('name')->all();

    expect($names('acm'))->toBe(['Acme'])
        ->and($names('glo'))->toBe([])
        // Matched from the start of a field, not mid-word.
        ->and($names('cme'))->toBe([]);
});
