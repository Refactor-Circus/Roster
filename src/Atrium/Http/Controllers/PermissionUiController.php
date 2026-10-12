<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Actions\DeletePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

final class PermissionUiController
{
    use AuthorizesScreens;

    public function index(Request $request): View
    {
        $this->authorizeScreen('roster.roles.view');

        /** @var view-string $view */
        $view = 'roster::ui.permissions.index';

        return view($view, [
            'permissions' => app(ListPermissionsAction::class)->execute(ListPermissionsData::from($request->query()))->withQueryString(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage');

        app(CreatePermissionAction::class)->execute(CreatePermissionData::from($request->all()));

        return $this->back('roster::roster.permission_created');
    }

    public function update(Request $request, string $permission): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage');

        $request->merge(['description' => $request->input('description')]);

        app(UpdatePermissionAction::class)->execute($this->find($permission), UpdatePermissionData::from($request->all()));

        return $this->back('roster::roster.permission_updated');
    }

    public function destroy(string $permission): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage');

        app(DeletePermissionAction::class)->execute($this->find($permission));

        return $this->back('roster::roster.permission_deleted');
    }

    private function find(string $name): PermissionModel
    {
        return PermissionModel::query()->where('name', $name)->firstOrFail();
    }

    private function back(string $message): RedirectResponse
    {
        return redirect()->route('atrium.roster.permissions.index')->with('status', __($message));
    }
}
