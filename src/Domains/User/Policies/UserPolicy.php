<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may manage users. Answers Roster's `roster.users.*` permissions, for
 * the user the call is about when everyone may do it to themselves.
 */
class UserPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.view', $subject, $self);
    }

    public function create(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.create', $subject, $self);
    }

    public function update(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.update', $subject, $self);
    }

    public function delete(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.delete', $subject, $self);
    }

    public function purge(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.purge', $subject, $self);
    }

    public function manageStatus(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.manage-status', $subject, $self);
    }

    public function approve(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.approve', $subject, $self);
    }

    public function impersonate(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.users.impersonate', $subject, $self);
    }
}
