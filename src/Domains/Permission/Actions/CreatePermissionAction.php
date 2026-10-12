<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionCreatedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionCreatingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;

final class CreatePermissionAction
{
    /**
     * Declare an app permission, e.g. `invoices.edit`, for roles to grant.
     */
    public function execute(CreatePermissionData $data): PermissionModel
    {
        PermissionCreatingActionEvent::dispatch($data);

        $permission = DB::transaction(fn (): PermissionModel => PermissionModel::query()->create([
            'name' => $data->name,
            'description' => $data->description,
            'system' => false,
        ]));

        app(Permissions::class)->flush();

        PermissionCreatedActionEvent::dispatch($permission);

        return $permission;
    }
}
