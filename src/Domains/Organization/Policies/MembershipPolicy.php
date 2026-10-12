<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may see and change an organization's members. Answers Roster's
 * `roster.members.*` permissions in the membership's organization.
 */
class MembershipPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.members.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.members.manage', $subject, $self);
    }
}
