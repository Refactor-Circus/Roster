<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim;

use Illuminate\Contracts\Auth\Access\Gate;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;
use RefactorCircus\Roster\Domains\Scim\Policies\ScimGroupPolicy;
use RefactorCircus\Roster\Domains\Scim\Policies\ScimTokenPolicy;
use RefactorCircus\Roster\Domains\Scim\Policies\ScimUserPolicy;
use RefactorCircus\Roster\Domains\Scim\Services\ScimContext;

/**
 * SCIM 2.0 provisioning and the tokens that authenticate it.
 */
class ScimServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->scoped(ScimContext::class);
    }

    public function boot(): void
    {
        $this->registerAuthorization();

        // Members SCIM provisions join with this source.
        $this->app->make(MembershipSources::class)->register('scim', 'SCIM');

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/scim.php');
    }

    /**
     * The `roster.scim.manage` permission, granted to admins, answered by the
     * token policy, through the same seams other packages use. An
     * application's `roster.policies` entry for these models still wins: the
     * package provider registers those after this boots.
     */
    private function registerAuthorization(): void
    {
        $this->app->make(Resources::class)->rosterResource('roster.scim', ScimTokenModel::class);

        $this->app->make(PermissionCatalogue::class)
            ->rosterPermission('roster.scim.manage', 'Issue and revoke SCIM provisioning tokens.', ['admin']);

        $gate = $this->app->make(Gate::class);
        $gate->policy(ScimTokenModel::class, ScimTokenPolicy::class);
        $gate->policy(ScimUserModel::class, ScimUserPolicy::class);
        $gate->policy(ScimGroupModel::class, ScimGroupPolicy::class);
    }
}
