<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use Illuminate\Foundation\Testing\DatabaseTransactionsManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Laravel\Mcp\Server\McpServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;
use RefactorCircus\Atrium\AtriumServiceProvider;
use RefactorCircus\Impex\ImpexServiceProvider;
use RefactorCircus\Roster\RosterServiceProvider;
use Workbench\App\Models\User;

use function Orchestra\Testbench\default_migration_path;

abstract class TestCase extends Orchestra
{
    /**
     * Whether to open Atrium to everyone, as surface tests need. Atrium
     * denies everything outside `local` until its gate is defined.
     */
    protected bool $openAtrium = true;

    /**
     * The test-case classes whose database this process has migrated.
     *
     * @var array<string, true>
     */
    private static array $migrated = [];

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->openAtrium) {
            Gate::define('viewAtrium', fn (): bool => true);
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            AtriumServiceProvider::class,
            ImpexServiceProvider::class,
            RosterServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        // refactor-circus/cortex is a dev dependency, so Atrium discovers its plugin here
        // without its migrations; its navigation would query missing tables.
        $app['config']->set('atrium.disabled', ['cortex']);

        $app['config']->set('app.key', 'base64:'.base64_encode(str_repeat('a', 32)));
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing.foreign_key_constraints', true);
        $app['config']->set('cache.default', 'array');
        // Minimum bcrypt cost: hashing is the slowest part of creating users,
        // and the workbench's .env (BCRYPT_ROUNDS=12) would otherwise leak in.
        $app['config']->set('hashing.bcrypt.rounds', 4);
        $app['config']->set('roster.users.model', User::class);
        // Surface tests need the API mounted; DefaultsTest covers it being off.
        $app['config']->set('roster.routes.enabled', true);
        // Surface tests cover behaviour; tests/Authorization turns it back on.
        $app['config']->set('roster.authorization', false);
        // Impex runs drive inline on the sync queue, so a CSV import or
        // export completes (or parks on its confirmation) within the call.
        $app['config']->set('queue.default', 'sync');
        $app['config']->set('filesystems.disks.local.root', sys_get_temp_dir().'/roster-tests-'.getmypid());

        $file = $this->databaseFile();

        if (! is_file($file)) {
            touch($file);
        }

        $app['config']->set('database.connections.testing.database', $file);
        // Throwaway data: skip fsyncs and keep the rollback journal in memory.
        $app['config']->set('database.connections.testing.synchronous', 'off');
        $app['config']->set('database.connections.testing.journal_mode', 'memory');
    }

    /**
     * Migrations run once per test-case class and parallel worker, into a
     * file database; each test then runs in a transaction that's rolled back.
     * Re-running 20 migrations for every test was most of the suite's time.
     */
    protected function defineDatabaseMigrations(): void
    {
        if (! isset(self::$migrated[$this->schemaKey()])) {
            Artisan::call('migrate:fresh', ['--path' => $this->migrationPaths(), '--realpath' => true]);
            $this->afterMigrating();
            self::$migrated[$this->schemaKey()] = true;
        }

        $this->beginTestTransaction();
    }

    /**
     * @return array<int, string>
     */
    protected function migrationPaths(): array
    {
        return [
            default_migration_path(),
            dirname(__DIR__).'/workbench/database/migrations',
            dirname(__DIR__).'/database/migrations',
            // Impex's migrations are publish-only; refactor-circus/impex is a dev dependency.
            dirname(__DIR__).'/vendor/refactor-circus/impex/database/migrations',
            // refactor-circus/keen is a dev dependency; KeenTestCase boots it.
            dirname(__DIR__).'/vendor/refactor-circus/keen/database/migrations',
        ];
    }

    /**
     * Schema a test case adds beyond the migrations (e.g. a custom users table).
     */
    protected function afterMigrating(): void {}

    /**
     * The same as Laravel's DatabaseTransactions, so after-commit callbacks
     * still fire when the test's own transaction level is reached.
     */
    private function beginTestTransaction(): void
    {
        $database = $this->app->make('db');
        $connection = $database->connection();
        $this->app->instance('db.transactions', $manager = new DatabaseTransactionsManager([$connection->getName()]));
        $connection->setTransactionManager($manager);
        $connection->beginTransaction();

        $this->beforeApplicationDestroyed(fn () => $connection->rollBack());
    }

    /**
     * One schema per test-case class (modes differ), per parallel worker.
     */
    private function schemaKey(): string
    {
        return get_parent_class($this) ?: static::class;
    }

    private function databaseFile(): string
    {
        return sys_get_temp_dir().'/roster-tests-'.md5($this->schemaKey()).'-'.(getenv('TEST_TOKEN') ?: '0').'.sqlite';
    }
}
