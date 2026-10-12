<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionsListedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\PermissionsListingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

final class ListPermissionsAction
{
    /**
     * @return LengthAwarePaginator<int, PermissionModel>
     */
    public function execute(ListPermissionsData $filters = new ListPermissionsData): LengthAwarePaginator
    {
        PermissionsListingActionEvent::dispatch($filters);

        $query = PermissionModel::query();
        $search = $filters->search;

        if ($search !== null && $search !== '') {
            $query->where('name', 'like', '%'.$search.'%');
        }

        $permissions = $query->orderBy('name')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        PermissionsListedActionEvent::dispatch($filters);

        return $permissions;
    }
}
