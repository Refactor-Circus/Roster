<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

beforeEach(function (): void {
    $this->actingAs(user(['name' => 'Admin']));
});

it('manages permissions', function (): void {
    $this->get(route('atrium.roster.permissions.index'))->assertOk()->assertSee('roster.users.view');

    $this->post(route('atrium.roster.permissions.store'), ['name' => 'invoices.edit', 'description' => 'Edit'])->assertRedirect();
    $this->patch(route('atrium.roster.permissions.update', 'invoices.edit'), ['description' => 'Change'])->assertRedirect();

    expect(PermissionModel::query()->where('name', 'invoices.edit')->sole()->description)->toBe('Change');

    $this->delete(route('atrium.roster.permissions.destroy', 'invoices.edit'))->assertRedirect();

    expect(PermissionModel::query()->where('name', 'invoices.edit')->exists())->toBeFalse();
});

it('manages roles', function (): void {
    $this->get(route('atrium.roster.roles.index'))->assertOk()->assertSee('Super admin');

    $this->post(route('atrium.roster.roles.store'), ['name' => 'Auditor', 'scope' => 'global', 'permissions' => ['roster.users.view']])->assertRedirect();
    $role = RoleModel::query()->where('slug', 'auditor')->sole();

    $this->get(route('atrium.roster.roles.show', $role->id))->assertOk()->assertSee('Auditor');

    $this->patch(route('atrium.roster.roles.update', $role->id), ['name' => 'Auditor'])->assertRedirect();

    // Unchecking every box clears the permissions.
    expect($role->permissions()->count())->toBe(0);

    $this->delete(route('atrium.roster.roles.destroy', $role->id))->assertRedirect(route('atrium.roster.roles.index'));
});

it('assigns and revokes from the user page', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $admin = RoleModel::query()->where('slug', 'admin')->sole();

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))->assertOk()->assertSee('Member');

    $this->post(route('atrium.roster.users.roles.store', $ada->getRouteKey()), ['role' => $admin->id, 'organization' => 'acme', 'team' => ''])->assertRedirect();

    $assignment = RoleAssignmentModel::query()->where('role_id', $admin->id)->sole();

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'roles']))->assertOk()->assertSee('Admin');

    $this->delete(route('atrium.roster.users.roles.destroy', [$ada->getRouteKey(), $assignment->id]))->assertRedirect();

    expect(RoleAssignmentModel::query()->whereKey($assignment->id)->exists())->toBeFalse();
});

it('keeps each permission row\'s buttons in its actions column, with the description input tied to its save form', function (): void {
    $this->actingAs(user());
    $permission = PermissionModel::query()->create(['name' => 'invoices.edit']);

    $html = $this->get(route('atrium.roster.permissions.index'))->assertOk()->assertSee('Actions')->getContent();

    expect($html)->toContain('id="update-'.$permission->id.'"')
        ->and($html)->toMatch('/<input[^>]*name="description"[^>]*form="update-'.$permission->id.'"|<input[^>]*form="update-'.$permission->id.'"[^>]*name="description"/');
});

it('offers only shared roles and the user\'s own organizations\' roles on the user page', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    RoleModel::factory()->create(['name' => 'Acme Auditor', 'scope' => 'organization', 'organization_id' => $acme->id]);
    RoleModel::factory()->create(['name' => 'Globex Auditor', 'scope' => 'organization', 'organization_id' => $globex->id]);

    $this->get(route('atrium.roster.users.show', $ada->getRouteKey()))
        ->assertOk()
        ->assertSee('Acme Auditor')
        ->assertSee('Super admin')
        ->assertDontSee('Globex Auditor');
});
