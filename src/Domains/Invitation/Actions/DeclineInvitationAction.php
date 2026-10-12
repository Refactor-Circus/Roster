<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Invitation\Concerns\ResolvesInvitations;
use RefactorCircus\Roster\Domains\Invitation\Data\DeclineInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationDeclinedActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationDecliningActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;

final class DeclineInvitationAction
{
    use ResolvesInvitations;

    /**
     * Decline an invitation as `$user`, whose email must match it.
     */
    public function execute(DeclineInvitationData $data, Model $user): InvitationModel
    {
        $invitation = $this->pendingInvitationFor($data->token, $user);

        InvitationDecliningActionEvent::dispatch($invitation, $user);

        DB::transaction(fn () => $invitation->update(['declined_at' => now()]));

        $invitation = $invitation->refresh()->load('organization');

        InvitationDeclinedActionEvent::dispatch($invitation, $user);

        return $invitation;
    }
}
