<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may see and manage teams. Answers Roster's `roster.teams.*`
 * permissions in the team, which count its organization's too.
 */
class TeamPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.teams.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.teams.manage', $subject, $self);
    }
}
