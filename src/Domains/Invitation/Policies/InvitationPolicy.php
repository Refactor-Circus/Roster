<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may see, send and revoke invitations. Answers Roster's
 * `roster.invitations.*` permissions in the invitation's organization.
 */
class InvitationPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.invitations.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.invitations.manage', $subject, $self);
    }
}
