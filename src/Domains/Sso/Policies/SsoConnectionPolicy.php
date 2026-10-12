<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may see and configure single sign-on connections. Answers Roster's
 * `roster.sso.*` permissions in the connection's organization.
 */
class SsoConnectionPolicy extends Policy
{
    public function view(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.sso.view', $subject, $self);
    }

    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.sso.manage', $subject, $self);
    }
}
