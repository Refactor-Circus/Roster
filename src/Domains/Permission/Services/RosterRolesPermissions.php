<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions as PermissionsContract;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Authorization\Contracts\Scope;
use RefactorCircus\Keystone\Authorization\ScopeChain;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

/**
 * Roster's own roles behind Keystone's authorization contracts, while the
 * Role and Permission domains still exist.
 *
 * @internal Interim: bound by PermissionServiceProvider so the role-based
 *           suite stays green while callers move onto the contracts, and
 *           deleted with the two domains.
 *
 * Answers come from the request's `Permissions` service (the role tables,
 * the owner rule and `roster.super_admins`); a `Scope` that grants an ability
 * by itself allows it too, and a super-admin may do anything. Role writes go
 * to the role tables and forget the user's remembered answers.
 */
final readonly class RosterRolesPermissions implements PermissionsContract, Roles
{
    public function __construct(
        private Container $container,
        private Repository $config,
        private Users $users,
    ) {}

    public function allows(?Authenticatable $user, string $ability, mixed $scope = null): bool
    {
        if (! $user instanceof Model) {
            return false;
        }

        if ($this->permissions()->isSuperAdmin($user)) {
            return true;
        }

        if ($scope instanceof Scope) {
            foreach (ScopeChain::of($scope) as $link) {
                if ($link->grantsByItself($user, $ability)) {
                    return true;
                }
            }
        }

        return $this->permissions()->allows($user, $ability, $this->rosterScope($scope));
    }

    /**
     * Null when the user holds the ability globally. Organizations come from
     * the user's organization roles and the organizations they own; no other
     * scope type is listed.
     */
    public function scopesWith(?Authenticatable $user, string $ability, string $scopeType): ?array
    {
        if (! $user instanceof Model) {
            return [];
        }

        if ($this->allows($user, $ability)) {
            return null;
        }

        $organization = new OrganizationModel;

        if ($scopeType !== $organization::class && $scopeType !== $organization->getMorphClass()) {
            return [];
        }

        return array_values($this->permissions()->organizationsWith($user, $ability) ?? []);
    }

    public function abilities(?Authenticatable $user, mixed $scope = null): array
    {
        if (! $user instanceof Model) {
            return [];
        }

        return array_values($this->permissions()->for($user, $this->rosterScope($scope)));
    }

    public function isSuperAdmin(?Authenticatable $user): bool
    {
        return $user instanceof Model && $this->permissions()->isSuperAdmin($user);
    }

    public function forget(?Authenticatable $user = null): void
    {
        if ($user instanceof Model) {
            $this->permissions()->flushUser($user);

            return;
        }

        $this->permissions()->forgetCache();
    }

    public function available(): bool
    {
        return true;
    }

    public function exists(string $role, ?Model $scope = null): bool
    {
        return $this->role($role, $scope) !== null;
    }

    public function assign(Authenticatable $user, string $role, ?Model $scope = null): bool
    {
        $found = $this->role($role, $scope);

        if (! $user instanceof Model || $found === null) {
            return false;
        }

        RoleAssignmentModel::query()->firstOrCreate([
            'role_id' => $found->getKey(),
            'user_id' => $user->getKey(),
            'organization_id' => $this->organizationKey($scope),
            'team_id' => $scope instanceof TeamModel ? $scope->getKey() : null,
        ]);

        $this->permissions()->flushUser($user);

        return true;
    }

    public function revoke(Authenticatable $user, ?Model $scope = null): void
    {
        if (! $user instanceof Model || ($scope !== null && ! $scope instanceof OrganizationModel && ! $scope instanceof TeamModel)) {
            return;
        }

        $this->exactly(RoleAssignmentModel::query()->where('user_id', $user->getKey()), $scope)->delete();

        $this->permissions()->flushUser($user);
    }

    public function revokeEverywhere(Authenticatable $user): void
    {
        if (! $user instanceof Model) {
            return;
        }

        RoleAssignmentModel::query()->where('user_id', $user->getKey())->delete();

        $this->permissions()->flushUser($user);
    }

    public function of(Authenticatable $user, ?Model $scope = null): array
    {
        if (! $user instanceof Model || ($scope !== null && ! $scope instanceof OrganizationModel && ! $scope instanceof TeamModel)) {
            return [];
        }

        $slugs = $this->exactly(RoleAssignmentModel::query()->where('user_id', $user->getKey()), $scope)
            ->with('role')
            ->get()
            ->map(fn (RoleAssignmentModel $assignment): string => (string) $assignment->role?->slug)
            ->filter(fn (string $slug): bool => $slug !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        return array_values($slugs);
    }

    /**
     * Users holding the ability through a global role (super roles count),
     * plus the verified `roster.super_admins`.
     */
    public function holders(string $ability): array
    {
        $keys = RoleAssignmentModel::query()
            ->whereNull('organization_id')
            ->whereNull('team_id')
            ->whereHas('role', fn (Builder $role): Builder => $role
                ->where('super', true)
                ->orWhereHas('permissions', fn (Builder $permission): Builder => $permission->where('name', $ability)))
            ->pluck('user_id')
            ->all();

        foreach ((array) $this->config->get('roster.super_admins', []) as $email) {
            $user = is_string($email) ? $this->users->findByEmail($email) : null;

            if ($user !== null && $this->users->emailVerified($user)) {
                $keys[] = $user->getKey();
            }
        }

        $keys = array_values(array_unique(array_filter($keys, fn (mixed $key): bool => is_int($key) || is_string($key)), SORT_REGULAR));
        sort($keys);

        return $keys;
    }

    /**
     * The request's role-based permissions, asked afresh so a scoped
     * instance is never kept past its request.
     */
    private function permissions(): Permissions
    {
        return $this->container->make(Permissions::class);
    }

    private function rosterScope(mixed $scope): OrganizationModel|TeamModel|null
    {
        return $scope instanceof OrganizationModel || $scope instanceof TeamModel ? $scope : null;
    }

    /**
     * The role with that slug for the scope: the organization's own first,
     * then a shared one.
     */
    private function role(string $slug, ?Model $scope): ?RoleModel
    {
        $type = match (true) {
            $scope === null => RoleScope::Global,
            $scope instanceof OrganizationModel => RoleScope::Organization,
            $scope instanceof TeamModel => RoleScope::Team,
            default => null,
        };

        if ($type === null) {
            return null;
        }

        $organization = $this->organizationKey($scope);

        return RoleModel::query()
            ->where('scope', $type)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $organization === null
                ? $query->whereNull('organization_id')
                : $query->whereNull('organization_id')->orWhere('organization_id', $organization))
            ->orderByRaw('organization_id is null')
            ->first();
    }

    private function organizationKey(?Model $scope): mixed
    {
        return match (true) {
            $scope instanceof OrganizationModel => $scope->getKey(),
            $scope instanceof TeamModel => $scope->organization_id,
            default => null,
        };
    }

    /**
     * Limit assignments to exactly that scope.
     *
     * @param  Builder<RoleAssignmentModel>  $query
     * @return Builder<RoleAssignmentModel>
     */
    private function exactly(Builder $query, OrganizationModel|TeamModel|null $scope): Builder
    {
        return match (true) {
            $scope instanceof TeamModel => $query->where('team_id', $scope->getKey()),
            $scope instanceof OrganizationModel => $query->where('organization_id', $scope->getKey())->whereNull('team_id'),
            default => $query->whereNull('organization_id')->whereNull('team_id'),
        };
    }
}
