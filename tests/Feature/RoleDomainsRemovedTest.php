<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Support\Tables;

/**
 * Roles and permissions are a role store's, behind Keystone's authorization
 * contract: Roster keeps none of its old Role and Permission domains.
 */
it('registers no role or permission route', function (): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn (RegisteredRoute $route): string => (string) $route->getName())
        ->filter(fn (string $name): bool => Str::is([
            'roster.roles.*',
            'roster.permissions.*',
            'roster.users.roles.*',
            'roster.users.permissions',
            'roster.organizations.role-assignments.index',
        ], $name))
        ->values()
        ->all();

    // The positive control: Roster's API routes are mounted.
    expect(Route::has('roster.users.index'))->toBeTrue()
        ->and($names)->toBe([]);
});

it('offers no role or permission tool', function (): void {
    $basenames = array_map(class_basename(...), RosterServer::TOOLS);

    expect($basenames)->toContain('ListUsersTool')
        ->not->toContain(
            'ListPermissionsTool',
            'CreatePermissionTool',
            'UpdatePermissionTool',
            'DeletePermissionTool',
            'ListRolesTool',
            'ShowRoleTool',
            'CreateRoleTool',
            'UpdateRoleTool',
            'DeleteRoleTool',
            'ListRoleAssignmentsTool',
            'AssignRoleTool',
            'RevokeRoleTool',
            'ListUserPermissionsTool',
        );
});

it('has no role or permission command', function (): void {
    $commands = array_keys(Artisan::all());

    expect($commands)->toContain('roster:purge-deleted')
        ->not->toContain('roster:sync-permissions')
        ->not->toContain('roster:grant-super-admin');
});

it('has no Role or Permission domain', function (): void {
    $root = dirname(__DIR__, 2).'/src/Domains';

    expect(is_dir($root.'/Role'))->toBeFalse()
        ->and(is_dir($root.'/Permission'))->toBeFalse();
});

it('has no :dataset class', function (string $class): void {
    expect(class_exists($class))->toBeFalse();
})->with([
    'RefactorCircus\Roster\Domains\Role\Models\RoleModel',
    'RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel',
    'RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles',
    'RefactorCircus\Roster\Domains\Permission\Models\PermissionModel',
    'RefactorCircus\Roster\Domains\Permission\Services\Permissions',
    'RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue',
    'RefactorCircus\Roster\Domains\Permission\Services\RosterRolesPermissions',
    'RefactorCircus\Roster\Database\Factories\RoleFactory',
    'RefactorCircus\Roster\Database\Factories\PermissionFactory',
]);

it('has no role or permission config', function (): void {
    /** @var array<string, mixed> $config */
    $config = require dirname(__DIR__, 2).'/config/roster.php';
    $policies = array_map(class_basename(...), array_keys((array) $config['policies']));

    expect($config)->not->toHaveKeys(['permission_cache', 'super_admins'])
        ->and($policies)->toContain('UserModel')
        ->not->toContain('RoleModel', 'PermissionModel', 'RoleAssignmentModel')
        ->and((array) $config['tables'])->toHaveKey('organizations')
        ->not->toHaveKeys(['permissions', 'roles', 'permission_role', 'role_assignments'])
        ->and(Tables::DEFAULTS)->not->toHaveKeys(['permissions', 'roles', 'permission_role', 'role_assignments']);
});

it('keeps the default member role and blocks any role store\'s role management while impersonating', function (): void {
    /** @var array<string, mixed> $config */
    $config = require dirname(__DIR__, 2).'/config/roster.php';

    expect($config['roles'])->toBe(['default_member' => 'member'])
        ->and($config['impersonation']['blocked'])->toBe([
            'roster.users.impersonate',
            'roster.users.delete',
            '*.roles.manage',
            '*.roles.assign',
        ]);
});
