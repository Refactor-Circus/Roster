<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission;

use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Console\Commands\SyncPermissionsCommand;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionDeletedEvent;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionSavedEvent;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCache;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Role\Events\RoleAssignedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleAssignmentDeletedEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleAssignmentSavedEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleDeletedEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleRevokedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleSavedEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Roster;

/**
 * Permissions, how they are checked, and Roster's place in Laravel's Gate.
 */
class PermissionServiceProvider extends ServiceProvider
{
    /**
     * The finished events that can change who holds which permission where,
     * or a user's current organization or team.
     */
    private const string CHANGES_ACCESS = '/^RefactorCircus\\\\Roster\\\\Domains\\\\(Role|Permission|Organization|Team|Invitation|User)\\\\Events\\\\/';

    /**
     * The finished events that change the permission names themselves.
     */
    private const string CHANGES_CATALOGUE = '/^RefactorCircus\\\\Roster\\\\Domains\\\\Permission\\\\Events\\\\/';

    /**
     * The finished events that change only the access of the user they name.
     */
    private const string CHANGES_ONE_USER = '/\\\\(ContextSwitched|DomainJoined|MemberAdded|MemberRemoved|TeamMemberAdded|TeamMemberRemoved|InvitationAccepted|InvitationDeclined|User[A-Za-z]+|ProfileUpdated)ActionEvent$/';

    public function register(): void
    {
        // Registries of code-defined state, filled from providers' boot().
        // Roster's core resources are there from the start, before any boot().
        $this->app->singleton(Resources::class, function (): Resources {
            $resources = new Resources;

            foreach (Authorizer::RESOURCES as $resource => $model) {
                $resources->rosterResource('roster.'.$resource, $model);
            }

            return $resources;
        });
        $this->app->singleton(PermissionCatalogue::class);

        $this->app->scoped(Permissions::class);
        // Scoped like the Permissions it uses, so a long-lived worker (Octane,
        // queues) never keeps one request's resolved permissions.
        $this->app->scoped(Authorizer::class);
    }

    public function boot(): void
    {
        $this->registerGate();

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');

        if ($this->app->runningInConsole()) {
            $this->commands([SyncPermissionsCommand::class]);
        }
    }

    /**
     * Resolve Roster permissions through Laravel's Gate, so `$user->can()`
     * and `@can` work with them, and give Atrium a gate when the app has not
     * defined one.
     */
    private function registerGate(): void
    {
        $gate = $this->app->make(Gate::class);

        $gate->before(function (mixed $user, string $ability, array $arguments): ?bool {
            if (! $user instanceof Model) {
                return null;
            }

            // Refused while impersonating, whoever is really at the keyboard.
            if ($this->app->make(ImpersonationContext::class)->isBlocked($ability)) {
                return false;
            }

            $permissions = $this->app->make(Permissions::class);

            if ($permissions->isSuperAdmin($user)) {
                return true;
            }

            if (! $permissions->knows($ability)) {
                return null;
            }

            $scope = $this->scopeFrom($arguments) ?? $permissions->contextScope($user);

            // Null rather than false: the app's own gates and policies still
            // get their say when the user lacks the permission.
            return $permissions->allows($user, $ability, $scope) ? true : null;
        });

        // Permissions and the current organization/team are remembered for the
        // request; anything that changes them (assigning a role, switching
        // context, joining) clears that, so later checks see the change. Only
        // Roster's domains that hold roles, memberships and context count:
        // other packages' actions, transfers and reads leave
        // the caches alone, so bulk work does not rebuild them every row. A
        // change confined to one user forgets only that user; the permission
        // names are forgotten only when a permission itself changes.
        $this->app->make(Dispatcher::class)->listen(ActionFinishedEvent::class, function (ActionFinishedEvent $event): void {
            if (preg_match(self::CHANGES_ACCESS, $event::class) !== 1 || preg_match('/(Listed|Shown)ActionEvent$/', $event::class) === 1) {
                return;
            }

            $user = $this->affectedUser($event);
            $catalogue = preg_match(self::CHANGES_CATALOGUE, $event::class) === 1;

            if ($user !== null) {
                // Resolved even when this request has not checked anything
                // yet, so the cache store forgets the user too.
                $this->app->make(Permissions::class)->flushUser($user);
            } else {
                if ($this->app->resolved(Permissions::class)) {
                    $this->app->make(Permissions::class)->flush($catalogue);
                }

                $this->app->make(PermissionCache::class)->forget($catalogue);
            }

            if ($this->app->resolved(Roster::class)) {
                $this->app->make(Roster::class)->forget();
            }
        });

        // Writes that skip the Actions (seeders, `roster:sync-permissions`)
        // still reach the cache store's copy through the model events.
        $this->app->make(Dispatcher::class)->listen(
            [PermissionSavedEvent::class, PermissionDeletedEvent::class],
            fn (): bool => $this->forgetCached(catalogue: true),
        );
        $this->app->make(Dispatcher::class)->listen(
            [RoleSavedEvent::class, RoleDeletedEvent::class, RoleAssignmentSavedEvent::class, RoleAssignmentDeletedEvent::class],
            fn (): bool => $this->forgetCached(catalogue: false),
        );

        $this->app->booted(function () use ($gate): void {
            $ability = $this->app->make(Repository::class)->get('atrium.gate', 'viewAtrium');

            if (is_string($ability) && ! $gate->has($ability)) {
                $gate->define($ability, fn (mixed $user): bool => $user instanceof Model
                    && $this->app->make(Permissions::class)->allows($user, 'atrium.view'));
            }
        });
    }

    /**
     * The one user a finished event changes access for, or null when it may
     * change anyone's (role permissions, ownership, organizations, teams).
     */
    private function affectedUser(ActionFinishedEvent $event): Model|int|string|null
    {
        if ($event instanceof RoleAssignedActionEvent || $event instanceof RoleRevokedActionEvent) {
            $user = $event->assignment->getAttribute('user_id');

            return is_int($user) || is_string($user) ? $user : null;
        }

        if (preg_match(self::CHANGES_ONE_USER, $event::class) !== 1 || ! property_exists($event, 'user')) {
            return null;
        }

        return $event->user instanceof Model ? $event->user : null;
    }

    /**
     * Drop the cache store's copy, when there is one.
     */
    private function forgetCached(bool $catalogue): bool
    {
        $cache = $this->app->make(PermissionCache::class);

        if ($cache->enabled()) {
            $cache->forget($catalogue);
        }

        return true;
    }

    /**
     * The organization or team a Gate check is about.
     *
     * @param  array<int|string, mixed>  $arguments
     */
    private function scopeFrom(array $arguments): OrganizationModel|TeamModel|null
    {
        foreach ($arguments as $argument) {
            if ($argument instanceof OrganizationModel || $argument instanceof TeamModel) {
                return $argument;
            }

            if ($argument instanceof Model && method_exists($argument, 'organization')) {
                $organization = $this->app->make(Authorizer::class)->parentOf($argument, 'organization');

                if ($organization instanceof OrganizationModel) {
                    return $organization;
                }
            }
        }

        return null;
    }
}
