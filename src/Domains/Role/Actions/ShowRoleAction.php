<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use RefactorCircus\Roster\Domains\Role\Events\RoleShowingActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleShownActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

final class ShowRoleAction
{
    public function execute(RoleModel $role): RoleModel
    {
        RoleShowingActionEvent::dispatch($role);

        $role->load(['permissions', 'organization']);

        RoleShownActionEvent::dispatch($role);

        return $role;
    }
}
