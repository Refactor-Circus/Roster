<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Events\RoleDeletedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleDeletingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

final class DeleteRoleAction
{
    /**
     * Delete a role and every assignment of it. Built-in roles stay.
     */
    public function execute(RoleModel $role): void
    {
        if ($role->system) {
            throw ValidationException::withMessages(['role' => __('roster::roster.cannot_delete_system')]);
        }

        RoleDeletingActionEvent::dispatch($role);

        DB::transaction(fn () => $role->delete());

        app(Permissions::class)->flush();

        RoleDeletedActionEvent::dispatch($role);
    }
}
