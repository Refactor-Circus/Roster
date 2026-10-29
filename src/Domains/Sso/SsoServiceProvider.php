<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Sso\Policies\SsoConnectionPolicy;
use RefactorCircus\Roster\Domains\Sso\Policies\SsoIdentityPolicy;
use RefactorCircus\Roster\Domains\Sso\Services\Sso;

/**
 * Single sign-on connections (OIDC, SAML, Entra ID) and linked identities.
 */
class SsoServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerAuthorization();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if (! $this->app->make(Sso::class)->available()) {
            return;
        }

        $this->loadRoutesFrom(__DIR__.'/web.php');

        // SAML identity providers post the signed assertion cross-site;
        // the assertion's signature, not a CSRF token, protects it.
        ValidateCsrfToken::except([trim((string) $this->app->make(Repository::class)->get('roster.sso.prefix', 'roster/sso'), '/').'/*/callback']);
    }

    /**
     * The `roster.sso.*` permissions, granted to admins, answered by the
     * connection policy, through the same seams other packages use. An
     * application's `roster.policies` entry for these models still wins: the
     * package provider registers those after this boots.
     */
    private function registerAuthorization(): void
    {
        $this->app->make(Resources::class)->rosterResource('roster.sso', SsoConnectionModel::class);

        $this->app->make(PermissionCatalogue::class)
            ->rosterPermission('roster.sso.view', "View an organization's single sign-on connections.", ['admin'])
            ->rosterPermission('roster.sso.manage', 'Configure single sign-on connections.', ['admin']);

        $gate = $this->app->make(Gate::class);
        $gate->policy(SsoConnectionModel::class, SsoConnectionPolicy::class);
        $gate->policy(SsoIdentityModel::class, SsoIdentityPolicy::class);
    }
}
