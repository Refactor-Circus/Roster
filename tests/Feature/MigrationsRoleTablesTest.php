<?php

declare(strict_types=1);

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Orchestra\Testbench\default_migration_path;

/**
 * The four role tables Roster used to create, which an upgraded install
 * still holds until the host drops them.
 */
const ROLE_TABLES = ['roster_permissions', 'roster_roles', 'roster_permission_role', 'roster_role_assignments'];

/**
 * The indexes Roster's index migrations used to add to the role tables.
 */
const ROLE_TABLE_INDEXES = [
    'roster_roles' => 'roster_roles_organization_id_lookup_index',
    'roster_role_assignments' => 'roster_role_assignments_organization_created_index',
];

/**
 * The `migrations` row the removed role-tables migration left behind.
 */
const ROLE_TABLES_MIGRATION = '2026_01_01_000003_create_roster_role_tables';

/**
 * Run a migrate command over Roster's migrations on this test's own
 * database, asserting it succeeds.
 */
function roleTablesMigrate(string $command): void
{
    $root = dirname(__DIR__, 2);

    $options = [
        '--database' => 'role_tables',
        '--path' => [default_migration_path(), $root.'/workbench/database/migrations', $root.'/database/migrations'],
        '--realpath' => true,
    ];

    if ($command !== 'migrate:status') {
        $options['--force'] = true;
    }

    $status = Artisan::call($command, $options);

    expect($status)->toBe(0, "{$command} failed:\n".Artisan::output());
}

/**
 * The role tables on this test's database, with their row counts.
 *
 * @return array<string, int>
 */
function roleTableRows(): array
{
    $rows = [];

    foreach (ROLE_TABLES as $table) {
        if (Schema::connection('role_tables')->hasTable($table)) {
            $rows[$table] = DB::connection('role_tables')->table($table)->count();
        }
    }

    return $rows;
}

/**
 * The role-table indexes Roster's migrations used to add that are there.
 *
 * @return list<string>
 */
function roleTableIndexes(): array
{
    $found = [];

    foreach (ROLE_TABLE_INDEXES as $table => $index) {
        if (Schema::connection('role_tables')->hasTable($table) && Schema::connection('role_tables')->hasIndex($table, $index)) {
            $found[] = $index;
        }
    }

    return $found;
}

/**
 * Bring this test's database to the state of an install made before Roster
 * gave up the role tables: Roster's tables, the four role tables as the
 * removed migration created them (with the indexes the index migrations
 * added) holding a row each, and the removed migration's `migrations` row.
 */
function roleTablesInstall(): void
{
    roleTablesMigrate('migrate');

    $schema = Schema::connection('role_tables');

    $schema->create('roster_permissions', function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->string('name')->unique();
        $table->string('description', 1000)->nullable();
        $table->boolean('system')->default(false);
        $table->timestamps();
    });

    $schema->create('roster_roles', function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->string('name');
        $table->string('slug');
        $table->string('scope', 32);
        $table->foreignUlid('organization_id')->nullable()->constrained('roster_organizations')->cascadeOnDelete();
        $table->string('description', 1000)->nullable();
        $table->boolean('super')->default(false);
        $table->boolean('system')->default(false);
        $table->timestamps();

        $table->index(['scope', 'organization_id', 'slug']);
        $table->index('organization_id', 'roster_roles_organization_id_lookup_index');
    });

    $schema->create('roster_permission_role', function (Blueprint $table): void {
        $table->foreignUlid('role_id')->constrained('roster_roles')->cascadeOnDelete();
        $table->foreignUlid('permission_id')->constrained('roster_permissions')->cascadeOnDelete();

        $table->primary(['role_id', 'permission_id']);
    });

    $schema->create('roster_role_assignments', function (Blueprint $table): void {
        $table->ulid('id')->primary();
        $table->foreignUlid('role_id')->constrained('roster_roles')->cascadeOnDelete();
        $table->unsignedBigInteger('user_id')->index();
        $table->foreignUlid('organization_id')->nullable()->index()->constrained('roster_organizations')->cascadeOnDelete();
        $table->foreignUlid('team_id')->nullable()->index()->constrained('roster_teams')->cascadeOnDelete();
        $table->timestamps();

        $table->unique(['role_id', 'user_id', 'organization_id', 'team_id']);
        $table->index(['user_id', 'organization_id', 'team_id']);
        $table->index(['organization_id', 'created_at', 'id'], 'roster_role_assignments_organization_created_index');
    });

    $db = DB::connection('role_tables');
    $now = now();
    $userId = $db->table('users')->insertGetId(['name' => 'Ada', 'email' => 'ada@acme.test', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now]);
    $organizationId = (string) Str::ulid();
    $permissionId = (string) Str::ulid();
    $roleId = (string) Str::ulid();

    $db->table('roster_organizations')->insert(['id' => $organizationId, 'name' => 'Acme', 'slug' => 'acme', 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_permissions')->insert(['id' => $permissionId, 'name' => 'roster.users.view', 'system' => true, 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_roles')->insert(['id' => $roleId, 'name' => 'Admin', 'slug' => 'admin', 'scope' => 'organization', 'system' => true, 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_permission_role')->insert(['role_id' => $roleId, 'permission_id' => $permissionId]);
    $db->table('roster_role_assignments')->insert(['id' => (string) Str::ulid(), 'role_id' => $roleId, 'user_id' => $userId, 'organization_id' => $organizationId, 'created_at' => $now, 'updated_at' => $now]);

    // It ran in the same batch as the rest of Roster's migrations.
    $db->table('migrations')->insert(['migration' => ROLE_TABLES_MIGRATION, 'batch' => (int) $db->table('migrations')->max('batch')]);
}

beforeEach(function (): void {
    // A database of its own, outside the suite's shared one. Foreign keys are
    // not enforced: SQLite would turn dropping Roster's organizations table
    // on rollback into a delete cascading into the role rows (MySQL and
    // PostgreSQL refuse to drop it instead), and this test is about what
    // Roster's migrations themselves do to the role tables.
    config()->set('database.connections.role_tables', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);
});

it('creates none of the role tables on a fresh database', function (): void {
    roleTablesMigrate('migrate');

    expect(Schema::connection('role_tables')->hasTable('roster_organizations'))->toBeTrue()
        ->and(roleTableRows())->toBe([]);
});

it('neither alters nor drops the role tables of an existing install through migrate, rollback and refresh', function (): void {
    roleTablesInstall();

    $kept = array_fill_keys(ROLE_TABLES, 1);
    $indexes = array_values(ROLE_TABLE_INDEXES);

    expect(roleTableRows())->toBe($kept)
        ->and(roleTableIndexes())->toBe($indexes);

    roleTablesMigrate('migrate');
    roleTablesMigrate('migrate:status');

    expect(roleTableRows())->toBe($kept)
        ->and(roleTableIndexes())->toBe($indexes);

    // Rolling Roster back drops its own tables and leaves the role tables,
    // their rows and their indexes alone.
    roleTablesMigrate('migrate:rollback');

    expect(Schema::connection('role_tables')->hasTable('roster_organizations'))->toBeFalse()
        ->and(roleTableRows())->toBe($kept)
        ->and(roleTableIndexes())->toBe($indexes);

    roleTablesMigrate('migrate');

    expect(Schema::connection('role_tables')->hasTable('roster_organizations'))->toBeTrue()
        ->and(roleTableRows())->toBe($kept)
        ->and(roleTableIndexes())->toBe($indexes);

    roleTablesMigrate('migrate:refresh');

    expect(roleTableRows())->toBe($kept)
        ->and(roleTableIndexes())->toBe($indexes);
});

it('ships no role-tables migration, and no index migration touches the role tables', function (): void {
    $root = dirname(__DIR__, 2).'/database/migrations';

    expect(glob($root.'/{,*/}'.ROLE_TABLES_MIGRATION.'.php', GLOB_BRACE))->toBe([]);

    foreach (['2026_01_01_000010_add_roster_lookup_indexes.php', '2026_10_12_000001_add_roster_performance_indexes.php'] as $migration) {
        expect((string) file_get_contents($root.'/'.$migration))
            ->not->toContain("'roles'")
            ->not->toContain("'role_assignments'");
    }
});
