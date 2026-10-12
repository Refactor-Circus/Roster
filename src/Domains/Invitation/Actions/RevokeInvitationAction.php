<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationRevokedActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationRevokingActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;

final class RevokeInvitationAction
{
    public function execute(InvitationModel $invitation): InvitationModel
    {
        if ($invitation->status() !== InvitationStatus::Pending) {
            throw ValidationException::withMessages([
                'invitation' => __('roster::roster.invitation_not_pending', ['status' => $invitation->status()->label()]),
            ]);
        }

        InvitationRevokingActionEvent::dispatch($invitation);

        DB::transaction(fn () => $invitation->update(['revoked_at' => now()]));

        $invitation = $invitation->refresh()->load('organization');

        InvitationRevokedActionEvent::dispatch($invitation);

        return $invitation;
    }
}
