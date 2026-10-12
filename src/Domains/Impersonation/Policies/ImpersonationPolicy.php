<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Impersonations need `roster.users.impersonate` to see, start or stop.
 */
class ImpersonationPolicy extends Policy
{
    public function view(?Model $user, ?ImpersonationModel $impersonation = null): bool
    {
        return $this->permits($user, 'roster.users.impersonate', $impersonation);
    }

    public function manage(?Model $user, ?ImpersonationModel $impersonation = null): bool
    {
        return $this->permits($user, 'roster.users.impersonate', $impersonation);
    }
}
