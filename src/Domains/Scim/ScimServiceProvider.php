<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim;

use Illuminate\Contracts\Auth\Access\Gate;
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Packages\PackageRegistry;
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
use RefactorCircus\Roster\Roster;
use Throwable;

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

        $this->registerAuditContext();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/scim.php');
    }

    /**
     * While Roster audits, the SCIM token a change came through joins the
     * context of Roster's own entries, through Keystone's AuditHooks as any
     * other package would add it. Decided once every provider has booted,
     * when `Support\Audit` registers Roster's hooks.
     */
    private function registerAuditContext(): void
    {
        $this->app->booted(function (): void {
            if (! $this->app->make(Roster::class)->auditing()) {
                return;
            }

            $this->app->make(AuditHooks::class)->context(fn (object $event): array => $this->auditContext($event));
        });
    }

    /**
     * The SCIM token behind a Roster entry. AuditHooks runs every context
     * hook for every package's events and catches nothing, so this keeps to
     * Roster's events and never breaks the entry: a failure is reported and
     * the entry is written without the token.
     *
     * @return array<string, mixed>
     */
    private function auditContext(object $event): array
    {
        try {
            if ($this->app->make(PackageRegistry::class)->for($event)?->key !== 'roster') {
                return [];
            }

            $token = $this->app->make(ScimContext::class)->token;

            return $token === null ? [] : ['scim_token' => ['id' => $token->id, 'name' => $token->name]];
        } catch (Throwable $e) {
            report($e);

            return [];
        }
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
