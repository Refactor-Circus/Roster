<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Organization\Events\MemberRemovedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\MemberRemovingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class RemoveMemberAction
{
    use ManagesMemberships;

    /**
     * Remove a member, and with them every team seat they held.
     */
    public function execute(OrganizationModel $organization, Model $user): void
    {
        if ($organization->isOwnedBy($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_remove_owner')]);
        }

        $membership = $organization->membershipFor($user)
            ?? throw ValidationException::withMessages(['user' => __('roster::roster.not_a_member')]);

        MemberRemovingActionEvent::dispatch($organization, $user);

        DB::transaction(function () use ($organization, $user, $membership): void {
            $membership->teams()->detach();
            $membership->delete();
            $this->revokeRoles($user, $organization);
            $this->forgetContext($user, $organization);
        });

        MemberRemovedActionEvent::dispatch($organization, $user);
    }
}
