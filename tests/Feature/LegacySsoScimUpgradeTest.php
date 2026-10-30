<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

use function Orchestra\Testbench\default_migration_path;

/**
 * The five SSO and SCIM tables an upgraded install still holds, by their
 * old `roster.tables` key.
 */
const LEGACY_SSO_SCIM_TABLES = [
    'sso_connections' => 'roster_sso_connections',
    'sso_identities' => 'roster_sso_identities',
    'scim_tokens' => 'roster_scim_tokens',
    'scim_users' => 'roster_scim_users',
    'scim_groups' => 'roster_scim_groups',
];

/**
 * The `migrations` rows the removed 000006 / 000007 files left behind.
 */
const LEGACY_SSO_SCIM_MIGRATIONS = [
    '2026_01_01_000006_create_roster_sso_tables',
    '2026_01_01_000007_create_roster_scim_tables',
];

/**
 * The full Roster migration set an app runs: Laravel's own tables, the
 * workbench's users columns, Roster's migrations and its optional
 * email-index migration.
 *
 * @return list<string>
 */
function legacyMigrationPaths(): array
{
    $root = dirname(__DIR__, 2);

    return [
        default_migration_path(),
        $root.'/workbench/database/migrations',
        $root.'/database/migrations',
        $root.'/database/migrations/email-index',
    ];
}

/**
 * Run a migrate command over the Roster migrations on the upgrade test's own
 * database, asserting it succeeds.
 */
function legacyMigrate(string $command): void
{
    $options = ['--database' => 'legacy', '--path' => legacyMigrationPaths(), '--realpath' => true];

    if ($command !== 'migrate:status') {
        $options['--force'] = true;
    }

    $status = Artisan::call($command, $options);

    expect($status)->toBe(0, "{$command} failed:\n".Artisan::output());
}

function legacyHasTable(string $table): bool
{
    return Schema::connection('legacy')->hasTable($table);
}

/**
 * The five tables that exist on the upgrade test's database, with their row
 * counts.
 *
 * @return array<string, int>
 */
function legacyRows(): array
{
    $rows = [];

    foreach (LEGACY_SSO_SCIM_TABLES as $table) {
        if (legacyHasTable($table)) {
            $rows[$table] = DB::connection('legacy')->table($table)->count();
        }
    }

    return $rows;
}

/**
 * Bring the upgrade test's database to an upgraded install's state: Roster's
 * tables, the five SSO and SCIM tables from the verbatim fixture with one row
 * each, and the `migrations` rows of the two removed files.
 */
function legacyInstall(): void
{
    legacyMigrate('migrate');

    /** @var array{sso: Migration, scim: Migration} $legacy */
    $legacy = require dirname(__DIR__).'/Fixtures/legacy_sso_scim_schema.php';

    DB::setDefaultConnection('legacy');
    $legacy['sso']->up();
    $legacy['scim']->up();

    $db = DB::connection('legacy');
    $now = now();
    $userId = $db->table('users')->insertGetId(['name' => 'Ada', 'email' => 'ada@acme.test', 'password' => 'x', 'created_at' => $now, 'updated_at' => $now]);
    $organizationId = (string) Str::ulid();
    $teamId = (string) Str::ulid();
    $connectionId = (string) Str::ulid();

    $db->table('roster_organizations')->insert(['id' => $organizationId, 'name' => 'Acme', 'slug' => 'acme', 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_teams')->insert(['id' => $teamId, 'organization_id' => $organizationId, 'name' => 'Ops', 'slug' => 'ops', 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_sso_connections')->insert(['id' => $connectionId, 'organization_id' => $organizationId, 'name' => 'Okta', 'slug' => 'acme-okta', 'protocol' => 'oidc', 'config' => 'encrypted', 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_sso_identities')->insert(['id' => (string) Str::ulid(), 'connection_id' => $connectionId, 'user_id' => $userId, 'subject' => 'okta|ada', 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_scim_tokens')->insert(['id' => (string) Str::ulid(), 'organization_id' => $organizationId, 'name' => 'Okta', 'token_hash' => str_repeat('a', 64), 'sso_connection_id' => $connectionId, 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_scim_users')->insert(['id' => (string) Str::ulid(), 'organization_id' => $organizationId, 'user_id' => $userId, 'external_id' => 'okta-ada', 'created_at' => $now, 'updated_at' => $now]);
    $db->table('roster_scim_groups')->insert(['id' => (string) Str::ulid(), 'organization_id' => $organizationId, 'team_id' => $teamId, 'external_id' => 'okta-ops', 'created_at' => $now, 'updated_at' => $now]);

    // They ran in the same batch as the rest of Roster's migrations.
    $batch = (int) $db->table('migrations')->max('batch');

    foreach (LEGACY_SSO_SCIM_MIGRATIONS as $migration) {
        $db->table('migrations')->insert(['migration' => $migration, 'batch' => $batch]);
    }
}

beforeEach(function (): void {
    // The upgrade runs on a database of its own, outside the suite's shared
    // one. Foreign keys are not enforced: SQLite would turn dropping a parent
    // table into an implicit delete cascading into the SSO and SCIM rows
    // (MySQL and PostgreSQL refuse to drop it instead), and this test is about
    // what Roster's migrations themselves do to those tables.
    config()->set('database.connections.legacy', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => false,
    ]);

    // An upgraded install's published config still names the five tables.
    foreach (LEGACY_SSO_SCIM_TABLES as $key => $table) {
        config()->set('roster.tables.'.$key, $table);
    }
});

afterEach(function (): void {
    DB::setDefaultConnection('testing');
});

it('creates none of the SSO and SCIM tables on a fresh database', function (): void {
    legacyMigrate('migrate');

    expect(legacyHasTable('roster_organizations'))->toBeTrue()
        ->and(legacyRows())->toBe([]);
});

it('keeps the SSO and SCIM tables and rows of an upgraded install through migrate, rollback and refresh', function (): void {
    legacyInstall();

    $kept = array_fill_keys(LEGACY_SSO_SCIM_TABLES, 1);

    expect(legacyRows())->toBe($kept);

    // The stray migrations rows fail neither migrate nor migrate:status.
    legacyMigrate('migrate');
    legacyMigrate('migrate:status');

    expect(legacyRows())->toBe($kept);

    // Rolling Roster back drops its own tables and leaves the five alone.
    legacyMigrate('migrate:rollback');

    expect(legacyHasTable('roster_organizations'))->toBeFalse()
        ->and(legacyRows())->toBe($kept);

    // The full Roster migration set runs on a database holding the five.
    legacyMigrate('migrate');

    expect(legacyHasTable('roster_organizations'))->toBeTrue()
        ->and(legacyRows())->toBe($kept);

    legacyMigrate('migrate:refresh');

    expect(legacyHasTable('roster_organizations'))->toBeTrue()
        ->and(legacyRows())->toBe($kept);
});

it('runs migrate:fresh on an upgraded install without error, creating no second set of tables', function (): void {
    legacyInstall();

    legacyMigrate('migrate:fresh');

    expect(legacyHasTable('roster_organizations'))->toBeTrue()
        ->and(legacyRows())->toBe([]);
});

it('ignores the stray migrations rows of the removed SSO and SCIM migrations', function (): void {
    legacyMigrate('migrate');

    $db = DB::connection('legacy');

    foreach (LEGACY_SSO_SCIM_MIGRATIONS as $migration) {
        $db->table('migrations')->insert(['migration' => $migration, 'batch' => 1]);
    }

    legacyMigrate('migrate');
    legacyMigrate('migrate:status');

    expect($db->table('migrations')->whereIn('migration', LEGACY_SSO_SCIM_MIGRATIONS)->count())->toBe(2)
        ->and(legacyRows())->toBe([]);
});

it('ships neither removed migration', function (): void {
    foreach (LEGACY_SSO_SCIM_MIGRATIONS as $migration) {
        expect(glob(dirname(__DIR__, 2).'/database/migrations/{,*/}'.$migration.'.php', GLOB_BRACE))->toBe([]);
    }
});
