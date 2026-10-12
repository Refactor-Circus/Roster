<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * Permissions are managed alongside roles: `roster.roles.view` to see them,
 * `roster.roles.manage` to create, edit and delete them.
 */
class PermissionPolicy extends Policy
{
    public function view(?Model $user, ?PermissionModel $permission = null): bool
    {
        return $this->permits($user, 'roster.roles.view', $permission);
    }

    public function manage(?Model $user, ?PermissionModel $permission = null): bool
    {
        return $this->permits($user, 'roster.roles.manage', $permission);
    }
}
