<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Events\UserPurgedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserPurgingActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Users;

final class PurgeUserAction
{
    public function __construct(private readonly Users $users) {}

    /**
     * Delete a user for good: their personal organization, memberships, roles
     * (revoked everywhere in the bound role store) and profile go with them. A model that soft-deletes must be
     * deleted (trashed) first. Owners of shared organizations - deleted ones
     * too - must hand them over first.
     */
    public function execute(Model $user, ?Model $actor = null): void
    {
        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_delete_self')]);
        }

        if ($this->users->softDeletes() && ! $this->users->trashed($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.delete_before_purge')]);
        }

        $owned = OrganizationModel::withTrashed()->where('owner_id', $user->getKey());

        if ((clone $owned)->where('personal', false)->exists()) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_delete_owner')]);
        }

        UserPurgingActionEvent::dispatch($user);

        DB::transaction(function () use ($user, $owned): void {
            $owned->get()->each(fn (OrganizationModel $organization) => app(PurgeOrganizationAction::class)->execute($organization, personal: true));
            MembershipModel::query()->where('user_id', $user->getKey())->delete();
            ProfileModel::query()->where('user_id', $user->getKey())->delete();

            if ($user instanceof Authenticatable) {
                app(Roles::class)->revokeEverywhere($user);
                app(Permissions::class)->forget($user);
            }

            $this->users->softDeletes() ? $user->forceDelete() : $user->delete();
        });

        UserPurgedActionEvent::dispatch($user);
    }
}
