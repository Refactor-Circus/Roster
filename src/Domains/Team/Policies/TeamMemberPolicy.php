<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * A team seat follows its team: `roster.teams.view` to see it,
 * `roster.teams.manage` to seat or unseat a member.
 */
class TeamMemberPolicy extends Policy
{
    public function view(?Model $user, ?TeamMemberModel $seat = null): bool
    {
        return $this->permits($user, 'roster.teams.view', $seat);
    }

    public function manage(?Model $user, ?TeamMemberModel $seat = null): bool
    {
        return $this->permits($user, 'roster.teams.manage', $seat);
    }
}
