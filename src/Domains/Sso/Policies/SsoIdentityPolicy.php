<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * A linked sign-in follows its connection: `roster.sso.view` to see it,
 * `roster.sso.manage` to unlink it.
 */
class SsoIdentityPolicy extends Policy
{
    public function view(?Model $user, ?SsoIdentityModel $identity = null): bool
    {
        return $this->permits($user, 'roster.sso.view', $identity?->connection);
    }

    public function manage(?Model $user, ?SsoIdentityModel $identity = null): bool
    {
        return $this->permits($user, 'roster.sso.manage', $identity?->connection);
    }
}
