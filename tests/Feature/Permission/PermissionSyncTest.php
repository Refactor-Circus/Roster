<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles;
use RefactorCircus\Roster\Support\Tables;

const DOMAIN_PERMISSIONS = ['roster.scim.manage', 'roster.sso.manage', 'roster.sso.view'];

/**
 * The built-in, shared role with this slug, if there is one.
 */
function builtInRole(string $slug): ?RoleModel
{
    return RoleModel::query()->whereNull('organization_id')->where('slug', $slug)->first();
}

/**
 * The names of the permissions a role holds, sorted.
 *
 * @return array<int, string>
 */
function heldBy(RoleModel $role): array
{
    return $role->permissions()->orderBy('name')->pluck('name')->map(strval(...))->all();
}

/**
 * Every permission row and every grant, to compare before and after.
 *
 * @return array{permissions: array<int, array<string, mixed>>, grants: array<int, array<string, mixed>>}
 */
function permissionSnapshot(): array
{
    return [
        'permissions' => PermissionModel::query()->orderBy('name')->get(['id', 'name', 'description', 'system'])->toArray(),
        'grants' => DB::table(Tables::name('permission_role'))->orderBy('role_id')->orderBy('permission_id')->get()
            ->map(fn (object $row): array => (array) $row)->all(),
    ];
}

it('drops the SSO and SCIM permissions from the core list', function (): void {
    expect(array_intersect(array_keys(BuiltInRoles::PERMISSIONS), DOMAIN_PERMISSIONS))->toBe([]);
});

it('no longer creates the SSO and SCIM permissions', function (): void {
    BuiltInRoles::sync();

    expect(PermissionModel::query()->whereIn('name', DOMAIN_PERMISSIONS)->exists())->toBeFalse();
});

/**
 * An upgraded install: the three SSO and SCIM permissions Roster used to
 * register, still stored as system permissions and granted to admin.
 */
function legacySsoScimPermissions(): RoleModel
{
    $admin = builtInRole('admin');

    foreach (DOMAIN_PERMISSIONS as $name) {
        $admin?->permissions()->attach(PermissionModel::query()->create(['name' => $name, 'description' => 'Old.', 'system' => true]));
    }

    return $admin ?? throw new RuntimeException('No built-in admin role.');
}

it('reports the stored SSO and SCIM permissions as unregistered, and keeps them and their admin grants', function (): void {
    $admin = legacySsoScimPermissions();
    $command = Artisan::all()['roster:sync-permissions'];
    $global = array_keys($command->getApplication()?->getDefinition()->getOptions() ?? []);
    // Every option the command itself offers (prune, force, delete, ...), one run each.
    $options = array_values(array_diff(array_keys($command->getDefinition()->getOptions()), $global));

    foreach ([[], ...array_map(fn (string $option): array => ['--'.$option => true], $options)] as $parameters) {
        $run = $this->artisan('roster:sync-permissions', $parameters);

        foreach (DOMAIN_PERMISSIONS as $name) {
            $run->expectsOutputToContain($name);
        }

        $run->assertSuccessful();
        $run->run();

        expect(PermissionModel::query()->whereIn('name', DOMAIN_PERMISSIONS)->where('system', true)->count())->toBe(3)
            ->and(heldBy($admin->fresh() ?? $admin))->toContain(...DOMAIN_PERMISSIONS);
    }
});

it('grants registered permissions to a built-in role sync creates', function (): void {
    builtInRole('admin')?->delete();
    app(PermissionCatalogue::class)->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);

    BuiltInRoles::sync();

    $admin = builtInRole('admin');

    expect($admin)->not->toBeNull()
        ->and($admin->system)->toBeTrue()
        ->and(heldBy($admin))->toContain('acme.widgets.approve')
        ->and(heldBy(builtInRole('member')))->not->toContain('acme.widgets.approve');
});

it('keeps the grants of an admin role the application edited', function (): void {
    $admin = builtInRole('admin');
    $admin->permissions()->detach(PermissionModel::query()->where('name', 'roster.organizations.update')->pluck('id'));
    $edited = heldBy($admin);

    BuiltInRoles::sync();

    expect(heldBy($admin))->toBe($edited)
        ->and(heldBy($admin))->not->toContain('roster.organizations.update');
});

it('leaves an install that already has every permission unchanged', function (): void {
    $before = permissionSnapshot();

    expect(BuiltInRoles::sync())->toBe([])
        ->and(permissionSnapshot())->toBe($before);
});

it('creates a permission registered after the migration ran, without granting it to existing roles', function (): void {
    app(PermissionCatalogue::class)->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);

    $this->artisan('roster:sync-permissions')->assertSuccessful();

    $permission = PermissionModel::query()->where('name', 'acme.widgets.approve')->first();

    expect($permission)->not->toBeNull()
        ->and($permission->system)->toBeTrue()
        ->and($permission->description)->toBe('Approve widgets.')
        // The existing admin role never gains it automatically.
        ->and(heldBy(builtInRole('admin')))->not->toContain('acme.widgets.approve');
});

it('warns about system permissions nothing registers, and keeps them', function (): void {
    PermissionModel::query()->create(['name' => 'legacy.reports.view', 'description' => 'Old.', 'system' => true]);
    PermissionModel::query()->create(['name' => 'custom.reports.view', 'description' => 'The app\'s own.', 'system' => false]);

    expect(BuiltInRoles::sync())->toBe(['legacy.reports.view']);

    $this->artisan('roster:sync-permissions')
        ->expectsOutputToContain('legacy.reports.view')
        ->doesntExpectOutputToContain('custom.reports.view')
        ->assertSuccessful();

    expect(PermissionModel::query()->where('name', 'legacy.reports.view')->exists())->toBeTrue();
});

it('creates registered permissions and their admin grants when the role tables migrate on a fresh database', function (): void {
    config(['database.connections.fresh' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
    DB::setDefaultConnection('fresh');

    // Registered before the migration, as a package's provider does in boot().
    app(PermissionCatalogue::class)->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);

    expect(Artisan::call('migrate', ['--database' => 'fresh', '--path' => $this->migrationPaths(), '--realpath' => true]))->toBe(0);

    $admin = builtInRole('admin');

    expect(PermissionModel::query()->whereIn('name', [...DOMAIN_PERMISSIONS, 'acme.widgets.approve'])->where('system', true)->pluck('name')->all())->toBe(['acme.widgets.approve'])
        ->and($admin)->not->toBeNull()
        ->and(heldBy($admin))->toContain('acme.widgets.approve');
});
