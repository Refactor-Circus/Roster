<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization;

use Illuminate\Auth\Events\Verified;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Routing\Router;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Organization\Http\Middleware\EnsureUserHasOrganization;
use RefactorCircus\Roster\Domains\Organization\Listeners\JoinOrganizationsOnVerified;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;

/**
 * Organizations, their members, domains, external links and the current context.
 */
class OrganizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Code-defined state, filled from providers' boot().
        $this->app->singleton(MembershipSources::class);
    }

    public function boot(): void
    {
        $this->app->make(Router::class)->aliasMiddleware('roster.organization', EnsureUserHasOrganization::class);
        $this->app->make(Dispatcher::class)->listen(Verified::class, JoinOrganizationsOnVerified::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }
}
