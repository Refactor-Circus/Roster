<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

it('manages permissions', function (): void {
    $this->postJson(route('roster.permissions.store'), ['name' => 'invoices.edit'])
        ->assertCreated()
        ->assertJsonPath('data', ['name' => 'invoices.edit', 'description' => null, 'system' => false]);

    $this->patchJson(route('roster.permissions.update', 'invoices.edit'), ['description' => 'Edit'])->assertOk()->assertJsonPath('data.description', 'Edit');
    $this->getJson(route('roster.permissions.index', ['search' => 'invoices']))->assertOk()->assertJsonPath('meta.total', 1);
    $this->deleteJson(route('roster.permissions.destroy', 'invoices.edit'))->assertNoContent();
    $this->deleteJson(route('roster.permissions.destroy', 'atrium.view'))->assertUnprocessable();
});

it('manages roles', function (): void {
    $id = $this->postJson(route('roster.roles.store'), ['name' => 'Auditor', 'scope' => 'global', 'permissions' => ['roster.users.view']])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'auditor')
        ->assertJsonPath('data.permissions', ['roster.users.view'])
        ->json('data.id');

    $this->patchJson(route('roster.roles.update', $id), ['permissions' => []])->assertOk()->assertJsonPath('data.permissions', []);
    $this->getJson(route('roster.roles.show', $id))->assertOk()->assertJsonPath('data.name', 'Auditor');
    $this->getJson(route('roster.roles.index', ['scope' => 'global']))->assertOk()->assertJsonPath('meta.total', 2);
    $this->deleteJson(route('roster.roles.destroy', $id))->assertNoContent();
});

it('assigns and revokes roles and reports permissions', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $admin = RoleModel::query()->where('slug', 'admin')->sole();

    $assignment = $this->postJson(route('roster.users.roles.store', $ada->getRouteKey()), ['role' => $admin->id, 'organization' => 'acme'])
        ->assertCreated()
        ->assertJsonPath('data.role.slug', 'admin')
        ->assertJsonPath('data.organization', 'acme')
        ->json('data.id');

    $this->getJson(route('roster.users.roles.index', $ada->getRouteKey()))->assertOk()->assertJsonPath('meta.total', 2);

    $this->getJson(route('roster.users.permissions', [$ada->getRouteKey(), 'organization' => 'acme']))
        ->assertOk()
        ->assertJsonPath('data.super_admin', false)
        ->assertJsonFragment(['roster.members.manage']);

    $this->deleteJson(route('roster.users.roles.destroy', [$ada->getRouteKey(), $assignment]))->assertNoContent();
});
