<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

require_once dirname(__DIR__).'/fixtures.php';

/**
 * What the role and permission routes and tools name, on Roster's role
 * tables: an organization `acme` with team `ops`, a member `target` holding
 * a custom global role, and an app permission `invoices.edit`.
 *
 * @return array<string, mixed>
 */
function roleApiWorld(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);
    $target = user(['email' => 'target@example.com']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $target->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));

    PermissionModel::query()->create(['name' => 'invoices.edit']);
    $role = RoleModel::factory()->create(['scope' => 'global', 'slug' => 'custom']);
    $assignment = RoleAssignmentModel::query()->create(['role_id' => $role->id, 'user_id' => $target->getKey()]);

    return compact('acme', 'target', 'ops', 'role', 'assignment');
}

/**
 * Every route of Roster's role and permission API: [method, name,
 * parameters, body].
 *
 * @return array<string, array{0: string, 1: string, 2: Closure(array<string, mixed>): array<int, mixed>, 3: array<string, mixed>}>
 */
function roleApiRoutes(): array
{
    $user = fn (array $w): array => [$w['target']->getRouteKey()];
    $none = fn (): array => [];

    return [
        'permissions.index' => ['GET', 'roster.permissions.index', $none, []],
        'permissions.store' => ['POST', 'roster.permissions.store', $none, ['name' => 'reports.view']],
        'permissions.update' => ['PATCH', 'roster.permissions.update', fn (): array => ['invoices.edit'], ['description' => 'x']],
        'permissions.destroy' => ['DELETE', 'roster.permissions.destroy', fn (): array => ['invoices.edit'], []],
        'roles.index' => ['GET', 'roster.roles.index', $none, []],
        'roles.store' => ['POST', 'roster.roles.store', $none, ['name' => 'R', 'scope' => 'global']],
        'roles.show' => ['GET', 'roster.roles.show', fn (array $w): array => [$w['role']->id], []],
        'roles.update' => ['PATCH', 'roster.roles.update', fn (array $w): array => [$w['role']->id], ['name' => 'R2']],
        'roles.destroy' => ['DELETE', 'roster.roles.destroy', fn (array $w): array => [$w['role']->id], []],
        'users.roles.index' => ['GET', 'roster.users.roles.index', $user, []],
        'users.roles.store' => ['POST', 'roster.users.roles.store', $user, ['role' => 'x']],
        'users.roles.destroy' => ['DELETE', 'roster.users.roles.destroy', fn (array $w): array => [$w['target']->getRouteKey(), $w['assignment']->id], []],
        'users.permissions' => ['GET', 'roster.users.permissions', $user, []],
        'organizations.role-assignments.index' => ['GET', 'roster.organizations.role-assignments.index', fn (): array => ['acme'], []],
    ];
}
