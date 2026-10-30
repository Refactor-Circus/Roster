<?php

declare(strict_types=1);

namespace RefactorCircus\Roster;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\ProvisionedStatus;
use RefactorCircus\Roster\Support\SignInRequirements;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;

/**
 * Resolves a user's working context: the organization and team they have
 * switched to. Also the seams other packages plug into at sign-in and
 * provisioning: sign-in requirements and the provisioned status.
 */
class Roster
{
    /**
     * Current organization and team per user, for this request. Cleared by
     * `forget()` whenever an Action changes something.
     *
     * @var array<string, OrganizationModel|null>
     */
    private array $organizations = [];

    /** @var array<string, TeamModel|null> */
    private array $teams = [];

    public function __construct(
        private readonly Users $users,
        private readonly SignInRequirements $signInRequirements,
    ) {}

    /**
     * Drop the remembered current organizations and teams.
     */
    public function forget(): void
    {
        $this->organizations = [];
        $this->teams = [];
    }

    /**
     * The user's current organization.
     *
     * Falls back to the organization they joined first when none is stored,
     * or when they have since left the stored one. Null when they belong to
     * no organization.
     */
    public function organization(Model $user): ?OrganizationModel
    {
        $key = $this->key($user);

        return array_key_exists($key, $this->organizations)
            ? $this->organizations[$key]
            : $this->organizations[$key] = $this->resolveOrganization($user);
    }

    private function resolveOrganization(Model $user): ?OrganizationModel
    {
        $profile = $this->users->profileIfExists($user);
        $stored = $profile?->current_organization_id;

        // The stored one while the user is still a member, in one query.
        // Deleted organizations don't count.
        $current = $stored === null ? null : OrganizationModel::query()
            ->whereKey($stored)
            ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
            ->first();

        if ($current !== null) {
            return $current;
        }

        $first = MembershipModel::query()->where('user_id', $user->getKey())->whereHas('organization')->oldest()->oldest('id')->first();

        return $first?->organization;
    }

    /**
     * Whether Roster records to the suite's audit log: while refactor-circus/keen
     * is installed, unless `roster.audit.enabled` is false.
     */
    public function auditing(): bool
    {
        return app(PackageRegistry::class)->get('roster')->audits();
    }

    /**
     * Register a sign-in requirement, from a provider's `boot()`. A
     * class-string is resolved from the container on the first
     * `externalSignInFor()`, once; the same class or instance registered
     * again is ignored. Requirements are asked in registration order and
     * must not claim the same emails.
     *
     * @param  SignInRequirement|class-string<SignInRequirement>  $requirement
     */
    public function requireSignIn(SignInRequirement|string $requirement): void
    {
        $this->signInRequirements->add($requirement);
    }

    /**
     * The sign-in this email must use instead of a password: the first
     * registered requirement's answer. Super-admins get null before any
     * requirement is asked, so a broken one cannot lock them out.
     *
     * Fails closed: a requirement that throws is not caught here, so the
     * login or registration asking fails and Laravel's handler reports it.
     */
    public function externalSignInFor(string $email): ?ExternalSignIn
    {
        if ($this->signInRequirements->isEmpty()) {
            return null;
        }

        $user = $this->users->findByEmail($email);

        if ($user !== null && app(Permissions::class)->isSuperAdmin($user)) {
            return null;
        }

        foreach ($this->signInRequirements->all() as $requirement) {
            $signIn = $requirement->for($email);

            if ($signIn !== null) {
                return $signIn;
            }
        }

        return null;
    }

    /**
     * The status the organization's externally provisioned users (SSO
     * just-in-time accounts, SCIM) start with: its `provisioned_status`,
     * resolved through the app's user statuses. No stored value starts them
     * active; a value that is not one of those statuses starts them active
     * too, and is reported, so provisioning never fails and a bad value is
     * never silent. Resolved by `Support\ProvisionedStatus` against
     * `roster.statuses.users`.
     */
    public function provisionedStatus(OrganizationModel $organization): AccountStatus
    {
        return (new ProvisionedStatus(Statuses::users()))->for($organization);
    }

    /**
     * The user's current team: the stored one, while it belongs to the
     * current organization and the user still holds a seat on it.
     */
    public function team(Model $user): ?TeamModel
    {
        $key = $this->key($user);

        return array_key_exists($key, $this->teams)
            ? $this->teams[$key]
            : $this->teams[$key] = $this->resolveTeam($user);
    }

    private function resolveTeam(Model $user): ?TeamModel
    {
        $organization = $this->organization($user);
        $stored = $this->users->profileIfExists($user)?->current_team_id;

        if ($organization === null || $stored === null) {
            return null;
        }

        // The stored team while it's in the organization and the user holds a
        // seat on it, in one query.
        $team = TeamModel::query()
            ->whereKey($stored)
            ->where('organization_id', $organization->getKey())
            ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
            ->first();

        // Permission checks in a team scope need its organization; it's this one.
        $team?->setRelation('organization', $organization);

        return $team;
    }

    /**
     * Resolve the current organization and team of many users at once - a
     * page of users - in two queries, so per-user lookups after it are free.
     * Gives the same answers as `organization()` and `team()`.
     *
     * @param  iterable<int, Model>  $users
     */
    public function preload(iterable $users): void
    {
        $users = collect($users)->reject(fn (Model $user): bool => array_key_exists($this->key($user), $this->teams));

        if ($users->isEmpty()) {
            return;
        }

        $memberships = MembershipModel::query()
            ->with('organization')
            ->whereHas('organization')
            ->whereIn('user_id', $users->map(fn (Model $user): mixed => $user->getKey())->all())
            ->oldest()
            ->oldest('id')
            ->get()
            ->groupBy(fn (MembershipModel $membership): string => (string) $membership->user_id);

        $current = [];

        foreach ($users as $user) {
            $mine = $memberships->get((string) $user->getKey(), collect());
            $profile = $this->users->profileIfExists($user);
            $membership = $mine->firstWhere('organization_id', $profile?->current_organization_id) ?? $mine->first();

            $this->organizations[$this->key($user)] = $membership?->organization;
            $current[$this->key($user)] = [$user, $membership, $profile?->current_team_id];
        }

        $teamIds = array_values(array_filter(array_column($current, 2)));
        $teams = $teamIds === [] ? collect() : TeamModel::query()->whereIn('id', $teamIds)->get()->keyBy('id');
        $seats = $teamIds === [] ? collect() : TeamMemberModel::query()
            ->whereIn('team_id', $teamIds)
            ->whereIn('membership_id', array_filter(array_map(fn (array $entry): mixed => $entry[1]?->getKey(), $current)))
            ->get(['team_id', 'membership_id'])
            ->map(fn (TeamMemberModel $seat): string => $seat->team_id.':'.$seat->membership_id);

        foreach ($current as $key => [$user, $membership, $teamId]) {
            $team = $teamId === null ? null : $teams->get($teamId);
            $seated = $team instanceof TeamModel && $membership !== null
                && $team->organization_id === $membership->organization_id
                && $seats->contains($team->id.':'.$membership->getKey());

            if ($seated) {
                $team->setRelation('organization', $membership->organization);
            }

            $this->teams[$key] = $seated ? $team : null;
        }
    }

    private function key(Model $user): string
    {
        return $user->getMorphClass().':'.$user->getKey();
    }
}
