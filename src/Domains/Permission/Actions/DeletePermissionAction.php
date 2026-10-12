<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionDeletedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionDeletingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;

final class DeletePermissionAction
{
    public function execute(PermissionModel $permission): void
    {
        if ($permission->system) {
            throw ValidationException::withMessages(['permission' => __('roster::roster.cannot_delete_system')]);
        }

        PermissionDeletingActionEvent::dispatch($permission);

        DB::transaction(fn () => $permission->delete());

        app(Permissions::class)->flush();

        PermissionDeletedActionEvent::dispatch($permission);
    }
}
