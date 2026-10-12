<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may manage organizations. Answers Roster's `roster.organizations.*`
 * permissions in the organization.
 */
class OrganizationPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.view', $subject, $self);
    }

    public function create(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.create', $subject, $self);
    }

    public function update(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.update', $subject, $self);
    }

    public function delete(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.delete', $subject, $self);
    }

    public function purge(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.purge', $subject, $self);
    }

    public function transfer(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.transfer', $subject, $self);
    }

    public function sync(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.organizations.sync', $subject, $self);
    }
}
