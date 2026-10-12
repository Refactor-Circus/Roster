<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Who may see a role assignment (`roster.roles.view`) and grant or revoke
 * one (`roster.roles.assign`), in its team or organization.
 */
class RoleAssignmentPolicy extends Policy
{
    public function view(?Model $user, ?RoleAssignmentModel $assignment = null): bool
    {
        return $this->permits($user, 'roster.roles.view', $assignment);
    }

    public function manage(?Model $user, ?RoleAssignmentModel $assignment = null): bool
    {
        return $this->permits($user, 'roster.roles.assign', $assignment);
    }
}
