<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
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
     */
    private function join(OrganizationModel $organization, Model $user, MembershipSource $source): MembershipModel
    {
        $membership = MembershipModel::query()->firstOrCreate(
            ['organization_id' => $organization->getKey(), 'user_id' => $user->getKey()],
            ['source' => $source],
        );

        if ($membership->wasRecentlyCreated && ! $organization->isOwnedBy($user)) {
            $this->assignDefaultRole($organization, $user);
        }

        return $membership;
    }

    private function assignDefaultRole(OrganizationModel $organization, Model $user): void
    {
        $slug = config('roster.roles.default_member');

        if (! is_string($slug) || $slug === '') {
            return;
        }

        $role = RoleModel::query()
            ->where('scope', RoleScope::Organization)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()))
            ->orderByRaw('organization_id is null')
            ->first();

        if ($role !== null) {
            RoleAssignmentModel::query()->firstOrCreate([
                'role_id' => $role->getKey(),
                'user_id' => $user->getKey(),
                'organization_id' => $organization->getKey(),
                'team_id' => null,
            ]);

            app(Permissions::class)->flushUser($user);
        }
    }

    /**
     * Drop the roles a user held in an organization (and its teams), or on
     * one team.
     */
    private function revokeRoles(Model $user, ?OrganizationModel $organization = null, ?TeamModel $team = null): void
    {
        $query = RoleAssignmentModel::query()->where('user_id', $user->getKey());

        if ($team !== null) {
            $query->where('team_id', $team->getKey());
        } elseif ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $query->delete();

        app(Permissions::class)->flushUser($user);
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
