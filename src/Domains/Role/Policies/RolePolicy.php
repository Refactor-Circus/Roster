<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may see, edit and assign roles. Answers Roster's `roster.roles.*`
 * permissions; an organization's own role is checked in that organization.
 */
class RolePolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.roles.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.roles.manage', $subject, $self);
    }

    public function assign(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.roles.assign', $subject, $self);
    }
}
