<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

final class UpdatePermissionAction
{
    /**
     * Change a permission's description. Names are permanent: code checks them.
     */
    public function execute(PermissionModel $permission, UpdatePermissionData $data): PermissionModel
    {
        PermissionUpdatingActionEvent::dispatch($permission, $data);

        DB::transaction(fn () => $permission->update(['description' => $data->description]));

        $permission = $permission->refresh();

        PermissionUpdatedActionEvent::dispatch($permission);

        return $permission;
    }
}
