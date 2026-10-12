<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Events\RoleCreatedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleCreatingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class CreateRoleAction
{
    use GuardsEscalation;
    use ResolvesScopes;

    /**
     * Create a role. With `organization` it is that organization's own
     * (organization or team scope only); without, every organization shares it.
     */
    public function execute(CreateRoleData $data, ?Model $actor = null): RoleModel
    {
        $scope = $data->scope;
        $organization = $this->organizationFrom($data->organization);
        $slug = $data->slug !== null && $data->slug !== '' ? $data->slug : Str::slug($data->name);
        $permissions = array_values($data->permissions);

        if ($organization !== null && $scope === RoleScope::Global) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.global_role_shared')]);
        }

        $taken = RoleModel::query()
            ->where('scope', $scope)
            ->where('slug', $slug)
            ->where(fn (Builder $query): Builder => $organization === null ? $query->whereNull('organization_id') : $query->where('organization_id', $organization->getKey()))
            ->exists();

        if ($taken || $slug === '') {
            throw ValidationException::withMessages(['slug' => __('roster::roster.role_slug_taken')]);
        }

        $this->guardEscalation($actor, $permissions, $organization, 'permissions');

        RoleCreatingActionEvent::dispatch($data);

        $role = DB::transaction(function () use ($data, $scope, $organization, $slug, $permissions): RoleModel {
            $role = RoleModel::query()->create([
                'name' => $data->name,
                'slug' => $slug,
                'scope' => $scope,
                'organization_id' => $organization?->getKey(),
                'description' => $data->description,
            ]);

            $role->permissions()->sync(PermissionModel::query()->whereIn('name', $permissions)->pluck('id'));

            return $role;
        });

        app(Permissions::class)->flush();

        $role->load(['permissions', 'organization']);

        RoleCreatedActionEvent::dispatch($role);

        return $role;
    }
}
