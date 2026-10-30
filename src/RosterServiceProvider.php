<?php

declare(strict_types=1);

namespace RefactorCircus\Roster;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Audit\History;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Packages\Package;
use RefactorCircus\Keystone\Support\PackageServiceProvider;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Roster\Atrium\AtriumServiceProvider;
use RefactorCircus\Roster\Console\Commands\PurgeDeletedCommand;
use RefactorCircus\Roster\Domains\DomainServiceProvider;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Support\Audit;
use RefactorCircus\Roster\Support\SignInRequirements;
use RefactorCircus\Roster\Support\Users;

/**
 * Roster's package-wide wiring: config, policies, views, translations,
 * publish tags, the MCP server, Cortex and the rate limiter. Each domain
 * under `Domains/` registers its own bindings, routes, listeners, commands
 * and morph aliases through its provider, listed in DomainServiceProvider.
 *
 * Roster describes itself to refactor-circus/keystone in `definition()`, so the shared
 * base classes find its config, routes and MCP server by namespace.
 *
 * Atrium is optional: Roster's Atrium seam (Atrium\AtriumServiceProvider)
 * is registered only while Atrium is installed.
 */
class RosterServiceProvider extends PackageServiceProvider
{
    /**
     * Skip the Atrium seam as if Atrium were not installed. For Roster's own
     * tests of the Atrium-free install, which still have Atrium autoloadable.
     *
     * @internal
     */
    public static bool $withoutAtrium = false;

    /**
     * Roster checks every call by default: `roster.authorization` is on
     * unless an application turns it off. The shared history route and tool
     * need `roster.audit.view` globally.
     */
    protected function definition(): Package
    {
        return Package::make('roster', __NAMESPACE__)
            ->label('Roster')
            ->server(RosterServer::class)
            ->authorization()
            ->authorizeHistory(fn (?Authenticatable $user): bool => $this->app->make(Authorizer::class)
                ->check($user instanceof Model ? $user : null, 'roster.audit.view'));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/roster.php', 'roster');
        $this->registerPackage();

        $this->app->scoped(Roster::class);
        $this->app->singleton(Users::class);

        // Sign-in requirements are code state, registered from providers'
        // boot(); Roster, which takes them, is rebuilt per request.
        $this->app->singleton(SignInRequirements::class);

        $this->app->register(DomainServiceProvider::class);

        // After the domains, so the seam's bindings replace their plain defaults.
        // A string, so nothing here loads Atrium when it is not installed.
        if (! self::$withoutAtrium && class_exists('RefactorCircus\Atrium\Atrium')) {
            $this->app->register(AtriumServiceProvider::class);
        }

        $this->registerSurfaces();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Cortex is optional: agents get the Roster tools only when it is loaded.
        $this->registerCortex();

        // Core models' policies, from `roster.policies`: Roster's own checks ask
        // them. The SSO and SCIM domains register theirs in their providers.
        $this->registerPolicies();
        $this->registerRateLimiter();
        $this->registerNotFoundResponses();

        $this->loadViewsFrom(__DIR__.'/../resources/views', 'roster');

        // The cached user counts per status go stale with any change Roster
        // makes to a user.
        Event::listen(ActionFinishedEvent::class, function (ActionFinishedEvent $event): void {
            if (str_starts_with($event::class, 'RefactorCircus\\Roster\\Domains\\User\\Events\\') && ! str_ends_with($event::class, 'ListedActionEvent') && ! str_ends_with($event::class, 'ShownActionEvent')) {
                Cache::forget(Users::STATUS_COUNTS_CACHE);
            }
        });

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'roster');

        $this->registerMcpServer();
        $this->loadHistoryRoutes();
        $this->registerAudit();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/roster.php' => config_path('roster.php'),
        ], ['roster', 'roster-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/roster'),
        ], ['roster', 'roster-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/roster'),
        ], ['roster', 'roster-lang']);

        $this->publishesMigrations([
            __DIR__.'/../database/migrations' => database_path('migrations'),
        ], ['roster', 'roster-migrations']);

        // Published copies are served instead of these; see Transfers::template().
        $this->publishes([
            __DIR__.'/../resources/import-templates' => resource_path('roster/import-templates'),
        ], ['roster', 'roster-import-templates']);

        $this->commands([PurgeDeletedCommand::class]);

        // Opt-in only, and deliberately not under the `roster` umbrella tag:
        // most apps already have a users table.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations/users' => database_path('migrations'),
        ], 'roster-users-migration');

        // Opt-in: a lower(email) expression index for PostgreSQL users tables.
        $this->publishesMigrations([
            __DIR__.'/../database/migrations/email-index' => database_path('migrations'),
        ], 'roster-email-index-migration');
    }

    /**
     * Roster's routes outside its JSON API: SCIM, and the signed pages a
     * person opens from an email. The shared surface is scoped, so name them
     * each time it is resolved.
     */
    private function registerSurfaces(): void
    {
        $this->app->afterResolving(Surface::class, function (Surface $surface): void {
            $surface->route('roster.scim.', 'scim')
                ->route('roster.invitations.page', 'web')
                ->route('roster.invitations.show', 'web')
                ->route('roster.impersonation.', 'web');
        });
    }

    /**
     * While Roster audits (refactor-circus/keen installed, `roster.audit.enabled`
     * on), teach the audit log about Roster's models and events, and let
     * Roster's global `roster.audit.view` read the whole log unless the
     * application defines `viewAuditLog` itself. Decided once every provider
     * has booted, so whether Keen is installed is known and an application's
     * own definition wins.
     */
    private function registerAudit(): void
    {
        $this->app->booted(function (): void {
            if (! $this->package()->audits()) {
                return;
            }

            $this->app->make(Audit::class)->register($this->app->make(AuditHooks::class));

            if (! Gate::has(History::ABILITY)) {
                Gate::define(History::ABILITY, fn (Model $user, mixed ...$arguments): bool => $this->app->make(Authorizer::class)->check($user, 'roster.audit.view'));
            }
        });
    }

    /**
     * The `roster` limiter behind `throttle:roster`: per user, or per IP for
     * guests. A null `roster.rate_limit.per_minute` turns it off.
     */
    private function registerRateLimiter(): void
    {
        RateLimiter::for('roster', function (Request $request): Limit {
            $perMinute = $this->app->make(Repository::class)->get('roster.rate_limit.per_minute');

            if (! is_numeric($perMinute)) {
                return Limit::none();
            }

            $user = $request->user();

            return Limit::perMinute((int) $perMinute)->by($user instanceof Model ? 'user:'.$user->getKey() : 'ip:'.$request->ip());
        });
    }
}
