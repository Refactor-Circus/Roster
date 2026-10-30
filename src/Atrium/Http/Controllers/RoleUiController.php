<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\DeleteRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Roster\Domains\Role\Actions\RevokeRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\ShowRoleAction;
use RefactorCircus\Roster\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Support\Scopes;
use RefactorCircus\Roster\Support\Users;

/**
 * The Atrium screens for roles, and for assigning them to users.
 */
final class RoleUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $filters = ListRolesData::from($request->query());
        $scope = Scopes::fromInput($filters->organization);
        $within = $this->authorizeList('roster.roles.view', $scope);

        $resolved = Atrium::screens()->resolve($this->screen('roster.roles', Screen::LIST)->tabs($this->overviewTab()), $request);

        /** @var view-string $view */
        $view = 'roster::ui.roles.index';

        return view($view, [
            'resolved' => $resolved,
            'filters' => $filters,
            // Where new roles go: the filtered organization, or global.
            'newRoleScope' => $scope,
            'scopes' => RoleScope::cases(),
            // The list only while it is the open tab. Each role's organization
            // is loaded with it, so its row slot asks no query per row.
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => [
                    'roles' => app(ListRolesAction::class)->execute($filters, $within)->withQueryString(),
                    'permissions' => app(ListPermissionsAction::class)->execute(new ListPermissionsData(perPage: 200))->items(),
                ],
                default => [],
            },
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.roles.manage', Scopes::fromInput($request->input('organization')));

        $role = app(CreateRoleAction::class)->execute(CreateRoleData::from($request->all()), $this->actor($request));

        return redirect()
            ->route('atrium.roster.roles.show', $role->id)
            ->with('status', __('roster::roster.role_created'));
    }

    public function show(Request $request, string $role): View
    {
        $model = $this->find($role);
        $this->authorizeScreen('roster.roles.view', $model->organization);

        $model = app(ShowRoleAction::class)->execute($model);
        $resolved = Atrium::screens()->resolve($this->screen('roster.role', Screen::DETAIL, $model)->tabs($this->overviewTab()), $request);

        /** @var view-string $view */
        $view = 'roster::ui.roles.show';

        return view($view, [
            'resolved' => $resolved,
            'role' => $model,
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => ['permissions' => app(ListPermissionsAction::class)->execute(new ListPermissionsData(perPage: 200))->items()],
                default => [],
            },
        ]);
    }

    public function update(Request $request, string $role): RedirectResponse
    {
        $model = $this->find($role);
        $this->authorizeScreen('roster.roles.manage', $model->organization);

        $request->merge(['permissions' => (array) $request->input('permissions', [])]);

        app(UpdateRoleAction::class)->execute($model, UpdateRoleData::from($request->all()), $this->actor($request));

        return redirect()
            ->route('atrium.roster.roles.show', $model->id)
            ->with('status', __('roster::roster.role_updated'));
    }

    public function destroy(string $role): RedirectResponse
    {
        $model = $this->find($role);
        $this->authorizeScreen('roster.roles.manage', $model->organization);

        app(DeleteRoleAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.roles.index')
            ->with('status', __('roster::roster.role_deleted'));
    }

    public function assign(Request $request, string $user): RedirectResponse
    {
        $target = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.roles.assign', Scopes::fromInput($request->input('organization'), $request->input('team')));

        app(AssignRoleAction::class)->execute($target, AssignRoleData::from($request->all()), $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.show', $target->getRouteKey())
            ->with('status', __('roster::roster.role_assigned'));
    }

    public function revoke(Request $request, string $user, string $assignment): RedirectResponse
    {
        $target = $this->users->findOrFail($user);
        $model = RoleAssignmentModel::query()->where('user_id', $target->getKey())->whereKey($assignment)->firstOrFail();
        $this->authorizeScreen('roster.roles.assign', $model->team ?? $model->organization);

        app(RevokeRoleAction::class)->execute($model, $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.show', $target->getRouteKey())
            ->with('status', __('roster::roster.role_revoked'));
    }

    private function find(string $id): RoleModel
    {
        return RoleModel::query()->whereKey($id)->firstOrFail();
    }
}
