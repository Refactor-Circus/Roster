<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Actions\DeletePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Actions\ListUserPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Data\ListUserPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\DeleteRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Roster\Domains\Role\Actions\RevokeRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

it('manages app permissions but keeps built-ins', function (): void {
    $permission = app(CreatePermissionAction::class)->execute(new CreatePermissionData(name: 'invoices.edit', description: 'Edit invoices'));
    $permission = app(UpdatePermissionAction::class)->execute($permission, new UpdatePermissionData(description: 'Change invoices'));

    expect($permission->description)->toBe('Change invoices');

    app(DeletePermissionAction::class)->execute($permission);

    expect(fn () => app(DeletePermissionAction::class)->execute(PermissionModel::query()->where('name', 'atrium.view')->sole()))
        ->toThrow(ValidationException::class);
    expect(validator(['name' => 'Not Valid'], CreatePermissionData::rules())->fails())->toBeTrue();
});

it('creates shared and organization roles', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    $shared = app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Billing', scope: RoleScope::Organization, permissions: ['roster.members.view']));
    $own = app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Billing', scope: RoleScope::Organization, organization: 'acme'));

    expect($shared->organization_id)->toBeNull()
        ->and($own->organization_id)->toBe($acme->id)
        ->and($shared->permissions->pluck('name')->all())->toBe(['roster.members.view'])
        ->and(fn () => app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Billing', scope: RoleScope::Organization)))->toThrow(ValidationException::class)
        ->and(fn () => app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'X', scope: RoleScope::Global, organization: 'acme')))->toThrow(ValidationException::class);

    $slugs = fn (array $filters): array => collect(app(ListRolesAction::class)->execute(ListRolesData::from($filters))->items())->map(fn (RoleModel $role): string => $role->slug.($role->organization_id ? '@own' : ''))->all();

    expect($slugs(['scope' => 'organization']))->not->toContain('billing@own')
        ->and($slugs(['scope' => 'organization', 'organization' => 'acme']))->toContain('billing', 'billing@own');
});

it('stops actors granting permissions they lack', function (): void {
    $actor = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.roles.manage', 'roster.users.view'])->id, 'user_id' => $actor->getKey()]);

    app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Viewer', scope: RoleScope::Global, permissions: ['roster.users.view']), $actor);

    expect(fn () => app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Deleter', scope: RoleScope::Global, permissions: ['roster.users.delete']), $actor))
        ->toThrow(ValidationException::class);

    $role = RoleModel::query()->where('slug', 'viewer')->sole();

    expect(fn () => app(UpdateRoleAction::class)->execute($role, new UpdateRoleData(permissions: ['roster.users.view', 'roster.users.delete']), $actor))
        ->toThrow(ValidationException::class);

    // Removing permissions is always allowed.
    expect(app(UpdateRoleAction::class)->execute($role, new UpdateRoleData(permissions: []), $actor)->permissions)->toBeEmpty();
});

it('assigns roles only in their scope', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    $member = RoleModel::query()->where('slug', 'member')->sole();
    $lead = RoleModel::query()->where('slug', 'lead')->sole();
    $super = RoleModel::query()->where('slug', 'super-admin')->sole();

    $assign = fn (RoleModel $role, array $scope = []) => app(AssignRoleAction::class)->execute($ada, AssignRoleData::from(['role' => $role->id] + $scope));

    expect(fn () => $assign($member))->toThrow(ValidationException::class)
        ->and(fn () => $assign($member, ['organization' => 'acme']))->toThrow(ValidationException::class);

    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));

    // The default member role is already held.
    expect(fn () => $assign($member, ['organization' => 'acme']))->toThrow(ValidationException::class)
        ->and(fn () => $assign($lead, ['organization' => 'acme', 'team' => 'ops']))->toThrow(ValidationException::class)
        ->and(fn () => $assign($super, ['organization' => 'acme']))->toThrow(ValidationException::class);

    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada->getRouteKey()));

    $assignment = $assign($lead, ['organization' => 'acme', 'team' => 'ops']);

    expect($assignment->team_id)->toBe($ops->id);

    $own = app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Own', scope: RoleScope::Organization, organization: organization(attributes: ['name' => 'Globex'])->slug));

    expect(fn () => $assign($own, ['organization' => 'acme']))->toThrow(ValidationException::class);
});

it('keeps super roles for super-admins', function (): void {
    $super = RoleModel::query()->where('slug', 'super-admin')->sole();
    $actor = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.roles.assign'])->id, 'user_id' => $actor->getKey()]);
    $ada = user();

    expect(fn () => app(AssignRoleAction::class)->execute($ada, new AssignRoleData(role: $super->id), $actor))->toThrow(ValidationException::class);

    grant($actor, 'super-admin');

    $assignment = app(AssignRoleAction::class)->execute($ada, new AssignRoleData(role: $super->id), $actor);
    $other = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.roles.assign'])->id, 'user_id' => $other->getKey()]);

    expect(fn () => app(RevokeRoleAction::class)->execute($assignment, $other))->toThrow(ValidationException::class);

    app(RevokeRoleAction::class)->execute($assignment, $actor);

    expect(RoleAssignmentModel::query()->whereKey($assignment->id)->exists())->toBeFalse();
});

it('deletes custom roles but not built-ins', function (): void {
    $role = app(CreateRoleAction::class)->execute(new CreateRoleData(name: 'Temp', scope: RoleScope::Global));

    app(DeleteRoleAction::class)->execute($role);

    expect(fn () => app(DeleteRoleAction::class)->execute(RoleModel::query()->where('slug', 'admin')->sole()))->toThrow(ValidationException::class);
});

it('reports effective permissions', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));

    $result = app(ListUserPermissionsAction::class)->execute($ada, new ListUserPermissionsData(organization: 'acme'));

    expect($result['super_admin'])->toBeFalse()
        ->and($result['permissions'])->toBe(['roster.members.view', 'roster.organizations.view', 'roster.teams.view']);
});
