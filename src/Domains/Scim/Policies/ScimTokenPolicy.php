<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may issue and revoke SCIM tokens. Answers Roster's `roster.scim.manage`
 * permission in the token's organization.
 */
class ScimTokenPolicy extends Policy
{
    public function manage(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'roster.scim.manage', $subject, $self);
    }
}
