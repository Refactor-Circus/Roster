<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;

/**
 * Atrium badge variants for Roster's enums.
 */
final class Badges
{
    /**
     * The Atrium colour of a status. Pending - awaiting someone's decision -
     * has its own colour, `info`, used by no other status. User,
     * organization and team statuses name their own (`badge()`).
     */
    public static function forStatus(Status|InvitationStatus|TransferStatus $status): string
    {
        if ($status instanceof Status) {
            return $status->badge();
        }

        return match ($status) {
            InvitationStatus::Pending, TransferStatus::AwaitingConfirmation => 'info',
            InvitationStatus::Accepted, TransferStatus::Completed => 'success',
            InvitationStatus::Revoked, TransferStatus::Failed => 'danger',
            TransferStatus::Validating, TransferStatus::Running => 'primary',
            InvitationStatus::Declined, InvitationStatus::Expired, TransferStatus::Cancelled, TransferStatus::Expired => 'neutral',
        };
    }
}
