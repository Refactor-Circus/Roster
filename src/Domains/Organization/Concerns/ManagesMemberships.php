<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

/**
 * Joining and leaving organizations and teams, shared by every Action that
 * changes membership.
 */
trait ManagesMemberships
{
    /**
     * Make the user a member. A new member gets the default member role from
     * `roster.roles.default_member`; owners need none.
     *
     * @param  MembershipSource|string  $source  a built-in source, or one registered with `MembershipSources`
     *
     * @throws InvalidArgumentException for a source nothing registers
     */
    private function join(OrganizationModel $organization, Model $user, MembershipSource|string $source): MembershipModel
    {
        if (is_string($source) && ! app(MembershipSources::class)->has($source)) {
            throw new InvalidArgumentException("The membership source [{$source}] is not registered: register it with MembershipSources::register().");
        }

        $membership = MembershipModel::query()->firstOrCreate(
            ['organization_id' => $organization->getKey(), 'user_id' => $user->getKey()],
            ['source' => $source instanceof MembershipSource ? $source->value : $source],
        );

        if ($membership->wasRecentlyCreated && ! $organization->isOwnedBy($user)) {
            $this->assignDefaultRole($organization, $user);
        }

        return $membership;
    }

    /**
     * Give a new member `roster.roles.default_member` in the organization,
     * through the bound role store. Skipped when the setting is empty or the
     * store does not know the role (none is bound, or it has no such role).
     */
    private function assignDefaultRole(OrganizationModel $organization, Model $user): void
    {
        $slug = config('roster.roles.default_member');

        if (! is_string($slug) || $slug === '' || ! $user instanceof Authenticatable) {
            return;
        }

        $roles = app(Roles::class);

        if (! $roles->exists($slug, $organization)) {
            return;
        }

        $roles->assign($user, $slug, $organization);

        app(Permissions::class)->forget($user);
    }

    /**
     * Take away the roles a user held on a team, or in an organization and
     * on each of its teams they sit on. Call it for an organization before
     * the user's seats are removed.
     */
    private function revokeRoles(Model $user, ?OrganizationModel $organization = null, ?TeamModel $team = null): void
    {
        if (! $user instanceof Authenticatable) {
            return;
        }

        $roles = app(Roles::class);

        if ($team !== null) {
            $roles->revoke($user, $team);
        } elseif ($organization !== null) {
            $roles->revoke($user, $organization);

            TeamModel::query()
                ->where('organization_id', $organization->getKey())
                ->whereHas('memberships', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
                ->get()
                ->each(fn (TeamModel $seated) => $roles->revoke($user, $seated));
        }

        app(Permissions::class)->forget($user);
    }

    private function seat(TeamModel $team, MembershipModel $membership): void
    {
        TeamMemberModel::query()->firstOrCreate([
            'team_id' => $team->getKey(),
            'membership_id' => $membership->getKey(),
        ]);
    }

    /**
     * Clear a user's stored context where it points at something they can
     * no longer use.
     */
    private function forgetContext(Model $user, ?OrganizationModel $organization = null, ?TeamModel $team = null): void
    {
        $profile = ProfileModel::query()->where('user_id', $user->getKey())->first();

        if ($profile === null) {
            return;
        }

        if ($organization !== null && $profile->current_organization_id === $organization->getKey()) {
            $profile->update(['current_organization_id' => null, 'current_team_id' => null]);
        }

        if ($team !== null && $profile->current_team_id === $team->getKey()) {
            $profile->update(['current_team_id' => null]);
        }
    }
}
