<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Support\Users;

/**
 * Resolves what a user may do.
 *
 * A user's permissions in a scope are the union of their global roles, their
 * roles in the scope's organization and, for a team, their roles on that
 * team. An organization's owner holds every permission within it, except the
 * global-only ones. A super-admin passes everything.
 *
 * Bound per request: results are memoized and must not outlive it.
 */
final class Permissions
{
    /**
     * Permissions that only make sense globally: an organization owner does
     * not get them by owning an organization.
     *
     * @var array<int, string>
     */
    public const array GLOBAL_ONLY_PREFIXES = ['roster.users.', 'roster.organizations.create', 'atrium.'];

    /** @var array<string, array<int, string>> */
    private array $resolved = [];

    /** @var array<string, bool> */
    private array $supers = [];

    /**
     * Each user's role assignments, one row per permission they grant:
     * loaded once per user, so every scope and permission after the first is
     * answered in memory.
     *
     * @var array<string, array<int, array{organization: string|null, team: string|null, super: bool, permission: string|null}>>
     */
    private array $grants = [];

    /**
     * The organizations each user owns, loaded when first needed.
     *
     * @var array<string, array<int, string>>
     */
    private array $owned = [];

    /** @var array<int, string>|null */
    private ?array $known = null;

    /**
     * Bumped whenever anything is forgotten, so memos built on these answers
     * (the Authorizer's) know to start over.
     */
    private int $generation = 0;

    public function __construct(
        private readonly Users $users,
        private readonly Repository $config,
        private readonly PermissionCache $cache,
    ) {}

    /**
     * Whether a permission with this name exists.
     */
    public function knows(string $permission): bool
    {
        return in_array($permission, $this->all(), true);
    }

    /**
     * Every permission name.
     *
     * @return array<int, string>
     */
    public function all(): array
    {
        return $this->known ??= $this->cache->names(
            fn (): array => PermissionModel::query()->orderBy('name')->pluck('name')->map(strval(...))->all(),
        );
    }

    public function allows(Model $user, string $permission, OrganizationModel|TeamModel|null $scope = null): bool
    {
        return in_array($permission, $this->for($user, $scope), true);
    }

    /**
     * The user's permissions in a scope: global only when `$scope` is null.
     *
     * @return array<int, string>
     */
    public function for(Model $user, OrganizationModel|TeamModel|null $scope = null): array
    {
        $key = $user->getMorphClass().':'.$user->getKey().':'.($scope === null ? 'global' : $scope::class.':'.$scope->getKey());

        if (isset($this->resolved[$key])) {
            return $this->resolved[$key];
        }

        if ($this->isSuperAdmin($user)) {
            return $this->resolved[$key] = $this->all();
        }

        $team = $scope instanceof TeamModel ? (string) $scope->getKey() : null;
        $organization = match (true) {
            $scope instanceof TeamModel => (string) $scope->organization_id,
            $scope instanceof OrganizationModel => (string) $scope->getKey(),
            default => null,
        };
        $names = [];

        foreach ($this->grantsOf($user) as $grant) {
            $applies = ($grant['organization'] === null && $grant['team'] === null)
                || ($organization !== null && $grant['organization'] === $organization && $grant['team'] === null)
                || ($team !== null && $grant['team'] === $team);

            if ($applies && $grant['permission'] !== null) {
                $names[] = $grant['permission'];
            }
        }

        $owns = $scope instanceof OrganizationModel
            ? $scope->isOwnedBy($user)
            : $organization !== null && in_array($organization, $this->ownedBy($user), true);

        if ($owns) {
            $names = array_merge($names, array_values(array_filter($this->all(), fn (string $name): bool => ! $this->isGlobalOnly($name))));
        }

        $names = array_values(array_unique(array_map('strval', $names)));
        sort($names);

        return $this->resolved[$key] = $names;
    }

    /**
     * The organizations a user holds a permission in, through an
     * organization role or by owning the organization. Null when they hold it
     * globally, so no organization limits them.
     *
     * @return array<int, int|string>|null
     */
    public function organizationsWith(Model $user, string $permission): ?array
    {
        if (in_array($permission, $this->for($user), true)) {
            return null;
        }

        $assigned = [];

        foreach ($this->grantsOf($user) as $grant) {
            if ($grant['organization'] !== null && $grant['team'] === null && $grant['permission'] === $permission) {
                $assigned[] = $grant['organization'];
            }
        }

        $owned = $this->isGlobalOnly($permission) ? [] : $this->ownedBy($user);

        return array_values(array_unique([...$assigned, ...$owned]));
    }

    /**
     * Super-admins hold a super role, or are listed in `roster.super_admins`
     * with a verified email - an unverified address proves nothing.
     */
    public function isSuperAdmin(Model $user): bool
    {
        $key = $user->getMorphClass().':'.$user->getKey();

        return $this->supers[$key] ??= $this->hasSuperRole($user) || $this->isConfiguredSuperAdmin($user);
    }

    /**
     * The scope a Gate check without an explicit organization or team uses:
     * the user's current team, else their current organization.
     */
    public function contextScope(Model $user): OrganizationModel|TeamModel|null
    {
        $roster = app(Roster::class);

        return $roster->team($user) ?? $roster->organization($user);
    }

    /**
     * Forget everything remembered for this request. With `$catalogue` false
     * the permission names are kept: only a change to the permissions
     * themselves alters them.
     */
    public function flush(bool $catalogue = true): void
    {
        $this->resolved = [];
        $this->supers = [];
        $this->grants = [];
        $this->owned = [];
        $this->generation++;

        if ($catalogue) {
            $this->known = null;
        }
    }

    /**
     * Forget what this request remembered about one user, for a change that
     * only touches their own roles, memberships or context. A key is the
     * configured user model's.
     */
    public function flushUser(Model|int|string $user): void
    {
        $key = $this->userKey($user);

        unset($this->supers[$key], $this->grants[$key], $this->owned[$key]);

        foreach (array_keys($this->resolved) as $resolved) {
            if (str_starts_with($resolved, $key.':')) {
                unset($this->resolved[$resolved]);
            }
        }

        $this->generation++;
        $this->cache->forgetUser($key);
    }

    /**
     * Forget everything, here and in the cache store
     * (`roster.permission_cache`): for roles, assignments or permissions
     * written without Roster's Actions or models.
     */
    public function forgetCache(): void
    {
        $this->flush();
        $this->cache->forget(catalogue: true);
    }

    /**
     * Changes each time anything is forgotten.
     */
    public function generation(): int
    {
        return $this->generation;
    }

    /**
     * The key a user's answers are remembered under.
     */
    public function userKey(Model|int|string $user): string
    {
        return $user instanceof Model
            ? $user->getMorphClass().':'.$user->getKey()
            : $this->users->newModel()->getMorphClass().':'.$user;
    }

    private function hasSuperRole(Model $user): bool
    {
        foreach ($this->grantsOf($user) as $grant) {
            if ($grant['super'] && $grant['organization'] === null && $grant['team'] === null) {
                return true;
            }
        }

        return false;
    }

    /**
     * The user's assignments with what each role grants: one query per user
     * per request.
     *
     * @return array<int, array{organization: string|null, team: string|null, super: bool, permission: string|null}>
     */
    private function grantsOf(Model $user): array
    {
        $key = $this->userKey($user);

        return $this->grants[$key] ??= $this->cache->grants($key, fn (): array => $this->loadGrants($user));
    }

    /**
     * @return array<int, array{organization: string|null, team: string|null, super: bool, permission: string|null}>
     */
    private function loadGrants(Model $user): array
    {
        $assignments = Tables::name('role_assignments');
        $roles = Tables::name('roles');
        $pivot = Tables::name('permission_role');
        $permissions = Tables::name('permissions');

        return RoleAssignmentModel::query()
            ->where($assignments.'.user_id', $user->getKey())
            ->join($roles, $roles.'.id', '=', $assignments.'.role_id')
            ->leftJoin($pivot, $pivot.'.role_id', '=', $assignments.'.role_id')
            ->leftJoin($permissions, $permissions.'.id', '=', $pivot.'.permission_id')
            ->toBase()
            ->get([$assignments.'.organization_id', $assignments.'.team_id', $roles.'.super', $permissions.'.name'])
            ->map(fn (object $row): array => [
                'organization' => isset($row->organization_id) ? (string) $row->organization_id : null,
                'team' => isset($row->team_id) ? (string) $row->team_id : null,
                'super' => (bool) ($row->super ?? false),
                'permission' => isset($row->name) ? (string) $row->name : null,
            ])
            ->values()
            ->all();
    }

    /**
     * The ids of the organizations the user owns.
     *
     * @return array<int, string>
     */
    private function ownedBy(Model $user): array
    {
        return $this->owned[$user->getMorphClass().':'.$user->getKey()] ??= OrganizationModel::query()
            ->where('owner_id', $user->getKey())
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->values()
            ->all();
    }

    private function isConfiguredSuperAdmin(Model $user): bool
    {
        $emails = array_map(
            fn (mixed $email): string => strtolower((string) $email),
            (array) $this->config->get('roster.super_admins', []),
        );

        $email = $this->users->email($user);

        return $email !== null
            && in_array(strtolower($email), $emails, true)
            && $this->users->emailVerified($user);
    }

    private function isGlobalOnly(string $permission): bool
    {
        foreach (self::GLOBAL_ONLY_PREFIXES as $prefix) {
            if (str_starts_with($permission, $prefix)) {
                return true;
            }
        }

        return false;
    }
}
