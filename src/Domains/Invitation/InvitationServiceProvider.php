<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Services\PlainInvitationPage;

/**
 * Email invitations to an organization, and the page they link to.
 */
class InvitationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The plain page; Roster's Atrium seam replaces it while Atrium is installed.
        $this->app->singleton(InvitationPage::class, PlainInvitationPage::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadPublicRoutes();
        $this->loadRoutesFrom(__DIR__.'/web.php');
    }

    /**
     * The public invitation lookup: on and off with the JSON API, under its
     * prefix and `roster.` names, but wrapped in
     * `roster.invitations.lookup_middleware` (public and rate-limited) and
     * never in the API's own middleware, which authenticates.
     */
    private function loadPublicRoutes(): void
    {
        $config = $this->app->make(Repository::class);

        if ($config->get('roster.routes.enabled') !== true || $this->routesAreCached()) {
            return;
        }

        /** @var string $prefix */
        $prefix = $config->get('roster.routes.prefix', 'roster');

        /** @var array<int, string> $middleware */
        $middleware = $config->get('roster.invitations.lookup_middleware', ['api', 'throttle:roster-invitation-lookup']);

        Route::prefix($prefix)->middleware($middleware)->name('roster.')->group(__DIR__.'/public.php');
    }
}
