<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\CreatePermissionTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\DeletePermissionTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\ListPermissionsTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\ListUserPermissionsTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\UpdatePermissionTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\AssignRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\CreateRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\DeleteRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\ListRoleAssignmentsTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\ListRolesTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\RevokeRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\ShowRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\UpdateRoleTool;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

it('manages permissions with parity to the http payload', function (): void {
    mcpTool(CreatePermissionTool::class, ['name' => 'invoices.edit'])->assertOk();
    mcpTool(UpdatePermissionTool::class, ['name' => 'invoices.edit', 'description' => 'Edit'])->assertOk();

    $http = test()->getJson(route('roster.permissions.index', ['search' => 'invoices']))->json();

    mcpTool(ListPermissionsTool::class, ['search' => 'invoices'])->assertOk()->assertStructuredContent([
        'data' => $http['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 1],
    ]);

    mcpTool(DeletePermissionTool::class, ['name' => 'invoices.edit'])->assertOk()->assertSee('Permission deleted.');
});

it('manages roles with parity to the http payload', function (): void {
    mcpTool(CreateRoleTool::class, ['name' => 'Auditor', 'scope' => 'global', 'permissions' => ['roster.users.view']])->assertOk();
    $role = RoleModel::query()->where('slug', 'auditor')->sole();
    $http = fn (): array => test()->getJson(route('roster.roles.show', $role->id))->json();

    mcpTool(ShowRoleTool::class, ['role' => $role->id])->assertOk()->assertStructuredContent($http());
    mcpTool(UpdateRoleTool::class, ['role' => $role->id, 'name' => 'Auditors'])->assertOk()->assertStructuredContent($http());

    $list = test()->getJson(route('roster.roles.index', ['scope' => 'global']))->json();
    mcpTool(ListRolesTool::class, ['scope' => 'global'])->assertOk()->assertStructuredContent([
        'data' => $list['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 2],
    ]);

    mcpTool(DeleteRoleTool::class, ['role' => $role->id])->assertOk()->assertSee('Role deleted.');
});

it('assigns, lists and revokes roles', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $admin = RoleModel::query()->where('slug', 'admin')->sole();

    mcpTool(AssignRoleTool::class, ['user' => $ada->getRouteKey(), 'role' => $admin->id, 'organization' => 'acme'])->assertOk();

    $http = test()->getJson(route('roster.users.roles.index', $ada->getRouteKey()))->json();
    mcpTool(ListRoleAssignmentsTool::class, ['user' => $ada->getRouteKey()])->assertOk()->assertStructuredContent([
        'data' => $http['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 50, 'total' => 2],
    ]);

    mcpTool(ListUserPermissionsTool::class, ['user' => $ada->getRouteKey(), 'organization' => 'acme'])
        ->assertOk()
        ->assertStructuredContent(test()->getJson(route('roster.users.permissions', [$ada->getRouteKey(), 'organization' => 'acme']))->json());

    $assignment = RoleAssignmentModel::query()->where('role_id', $admin->id)->sole();
    mcpTool(RevokeRoleTool::class, ['user' => $ada->getRouteKey(), 'assignment' => $assignment->id])->assertOk()->assertSee('Role revoked.');
});
