<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Keystone\Data\Missing;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Roster\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Roster\Domains\Role\Events\RoleUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

final class UpdateRoleAction
{
    use GuardsEscalation;

    /**
     * Rename a role or replace its permissions. A given `permissions` list
     * replaces the old one; newly added permissions must be held by the actor.
     */
    public function execute(RoleModel $role, UpdateRoleData $data, ?Model $actor = null): RoleModel
    {
        $permissions = $data->permissions;

        if (! $permissions instanceof Missing) {
            $current = $role->permissions()->pluck('name')->all();
            $added = array_values(array_diff($permissions, $current));

            $this->guardEscalation($actor, $added, $role->organization, 'permissions');
        }

        RoleUpdatingActionEvent::dispatch($role, $data);

        DB::transaction(function () use ($role, $data, $permissions): void {
            $role->update(Arr::only($data->toArray(), ['name', 'description']));

            if (! $permissions instanceof Missing) {
                $role->permissions()->sync(PermissionModel::query()->whereIn('name', $permissions)->pluck('id'));
            }
        });

        app(Permissions::class)->flush();

        $role = $role->refresh()->load(['permissions', 'organization']);

        RoleUpdatedActionEvent::dispatch($role);

        return $role;
    }
}
