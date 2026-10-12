<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\User\Events\UserDeletedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserDeletingActionEvent;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

final class DeleteUserAction
{
    /**
     * Delete a user. A model using SoftDeletes keeps its profile, memberships
     * and personal organization so a restore brings it all back; a hard
     * delete removes them too. Owners of shared organizations must transfer
     * them first.
     */
    public function execute(Model $user, ?Model $actor = null): void
    {
        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages([
                'user' => __('roster::roster.cannot_delete_self'),
            ]);
        }

        $owned = OrganizationModel::query()->where('owner_id', $user->getKey());

        if ((clone $owned)->where('personal', false)->exists()) {
            throw ValidationException::withMessages([
                'user' => __('roster::roster.cannot_delete_owner'),
            ]);
        }

        UserDeletingActionEvent::dispatch($user);

        DB::transaction(function () use ($user, $owned): void {
            if (! in_array(SoftDeletes::class, class_uses_recursive($user), true)) {
                $owned->get()->each(fn (OrganizationModel $organization) => app(PurgeOrganizationAction::class)->execute($organization, personal: true));
                MembershipModel::query()->where('user_id', $user->getKey())->delete();
                RoleAssignmentModel::query()->where('user_id', $user->getKey())->delete();
                ProfileModel::query()->where('user_id', $user->getKey())->delete();
            }

            if (in_array(SoftDeletes::class, class_uses_recursive($user), true)) {
                // Their personal organization goes to Deleted with them.
                $owned->where('personal', true)->get()->each(fn (OrganizationModel $organization): ?bool => $organization->delete());
            }

            $user->delete();
        });

        UserDeletedActionEvent::dispatch($user);
    }
}
