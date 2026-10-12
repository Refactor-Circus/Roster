<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Policies\OrganizationPolicy;

/**
 * An application's policy: organizations can no longer be edited, by anyone.
 */
final class FrozenOrganizationPolicy extends OrganizationPolicy
{
    public function update(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return false;
    }
}
