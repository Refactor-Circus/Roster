<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Permission\Actions\ListUserPermissionsAction;
use RefactorCircus\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\User\Actions\ApproveUserAction;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\DeactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;
use RefactorCircus\Roster\Domains\User\Actions\ListUsersAction;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;
use RefactorCircus\Roster\Domains\User\Actions\ReactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\RejectUserAction;
use RefactorCircus\Roster\Domains\User\Actions\RestoreUserAction;
use RefactorCircus\Roster\Domains\User\Actions\ShowUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\DeactivateUserData;
use RefactorCircus\Roster\Domains\User\Data\ListUsersData;
use RefactorCircus\Roster\Domains\User\Data\RejectUserData;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Data\UpdateUserData;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;

/**
 * The Atrium screens for users. Every screen validates with its Action's own
 * rules and calls that Action, so the dashboard, HTTP API and MCP behave the
 * same.
 */
final class UserUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $this->authorizeScreen('roster.users.view');

        $resolved = Atrium::screens()->resolve($this->screen('roster.users', Screen::LIST)->tabs($this->overviewTab()), $request);
        $filters = ListUsersData::from($request->query());

        /** @var view-string $view */
        $view = 'roster::ui.users.index';

        return view($view, [
            'resolved' => $resolved,
            'filters' => $filters,
            'softDeletes' => $this->users->softDeletes(),
            'statuses' => Statuses::users()::cases(),
            'directory' => $this->users,
            // The list only while it is the open tab.
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => ['users' => app(ListUsersAction::class)->execute($filters)->withQueryString()],
                default => [],
            },
        ]);
    }

    public function create(Request $request): View
    {
        $this->authorizeScreen('roster.users.create');

        /** @var view-string $view */
        $view = 'roster::ui.users.create';

        return view($view, [
            'resolved' => Atrium::screens()->resolve($this->screen('roster.user-create', Screen::DETAIL)->tabs($this->overviewTab()), $request),
            'hasName' => $this->users->column('name') !== null,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $this->authorizeScreen('roster.users.create');

        $this->dropBlankPassword($request);

        $user = app(CreateUserAction::class)->execute(CreateUserData::from($request->all()));

        return redirect()
            ->route('atrium.roster.users.show', $user->getRouteKey())
            ->with('status', __('roster::roster.user_created'));
    }

    public function show(Request $request, string $user): View
    {
        $target = $this->users->findWithTrashedOrFail($user);
        $this->authorizeScreen('roster.users.view', null, $target);

        if ($this->users->trashed($target)) {
            /** @var view-string $deleted */
            $deleted = 'roster::ui.users.deleted';

            return view($deleted, ['user' => $target, 'directory' => $this->users]);
        }

        $model = app(ShowUserAction::class)->execute($target);

        // Only after the screen's own check: hosts' closures never run for a
        // viewer it refuses. A hidden registered ?tab= answers 403 here.
        $resolved = Atrium::screens()->resolve($this->screen('roster.user', Screen::DETAIL, $model)->tabs($this->overviewTab('user-circle')), $request);

        /** @var view-string $view */
        $view = 'roster::ui.users.show';

        return view($view, [
            'resolved' => $resolved,
            'user' => $model,
            'profile' => $model->getRelation('rosterProfile'),
            'status' => $this->users->status($model),
            'directory' => $this->users,
            // The overview's own data only while it is the open tab.
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => $this->overview($model),
                default => [],
            },
        ]);
    }

    public function update(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update');

        $this->dropBlankPassword($request);

        app(UpdateUserAction::class)->execute($model, UpdateUserData::from($request->all(), $model));

        return $this->backTo($model, 'roster::roster.user_updated');
    }

    public function profile(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update', null, $model);

        app(UpdateProfileAction::class)->execute($model, UpdateProfileData::from($request->all()));

        return $this->backTo($model, 'roster::roster.profile_updated');
    }

    /**
     * The user page's single status form: the new status picks the Action.
     */
    public function status(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.manage-status');

        $target = $request->validate(['status' => ['required', Rule::in([Statuses::users()::active()->value, Statuses::users()::suspended()->value, Statuses::users()::deactivated()->value])]])['status'];

        return match (Statuses::user($target)) {
            Statuses::users()::suspended() => $this->suspend($request, $user),
            Statuses::users()::deactivated() => $this->deactivate($request, $user),
            default => $this->reactivate($request, $user),
        };
    }

    public function approve(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.approve');

        app(ApproveUserAction::class)->execute($model, $this->actor($request));

        // Back to wherever it was clicked: the user's page, the Users list
        // or an organization's members.
        return redirect()->back(fallback: route('atrium.roster.users.show', $model->getRouteKey()))
            ->with('status', __('roster::roster.user_approved'));
    }

    public function reject(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.approve');

        app(RejectUserAction::class)->execute($model, RejectUserData::from($request->all()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_rejected');
    }

    public function suspend(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.manage-status');

        app(SuspendUserAction::class)->execute($model, SuspendUserData::from($request->all()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_suspended');
    }

    public function deactivate(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.manage-status');

        app(DeactivateUserAction::class)->execute($model, DeactivateUserData::from($request->all()), $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_deactivated');
    }

    public function reactivate(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.manage-status');

        app(ReactivateUserAction::class)->execute($model, $this->actor($request));

        return $this->backTo($model, 'roster::roster.user_reactivated');
    }

    public function switchContext(Request $request, string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update', null, $model);

        app(SwitchContextAction::class)->execute($model, SwitchContextData::from($request->all()));

        return $this->backTo($model, 'roster::roster.context_switched');
    }

    public function domainJoin(string $user): RedirectResponse
    {
        $model = $this->users->findOrFail($user);
        $this->authorizeScreen('roster.users.update');

        $joined = app(JoinOrganizationsByDomainAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.users.show', $model->getRouteKey())
            ->with('status', __('roster::roster.domain_joined', ['count' => $joined->count()]));
    }

    public function restore(string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.delete');

        $model = app(RestoreUserAction::class)->execute($this->users->findWithTrashedOrFail($user));

        return $this->backTo($model, 'roster::roster.user_restored');
    }

    public function purge(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.purge');
        $request->validate(['confirm' => ['accepted']]);

        app(PurgeUserAction::class)->execute($this->users->findWithTrashedOrFail($user), $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.index', ['trashed' => 'only'])
            ->with('status', __('roster::roster.deleted_permanently'));
    }

    public function destroy(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.delete');
        // The page asks for a ticked "I understand" before deleting.
        $request->validate(['confirm' => ['accepted']]);

        app(DeleteUserAction::class)->execute($this->users->findOrFail($user), $this->actor($request));

        return redirect()
            ->route('atrium.roster.users.index', $this->users->softDeletes() ? ['trashed' => 'only'] : [])
            ->with('status', __('roster::roster.user_deleted'));
    }

    /**
     * Everything the overview tab shows: the page's own content. Not loaded
     * while a registered tab is open.
     *
     * @return array<string, mixed>
     */
    private function overview(Model $model): array
    {
        $memberships = MembershipModel::query()->where('user_id', $model->getKey())->whereHas('organization')->with(['organization', 'teams'])->get();

        return [
            'memberships' => $memberships,
            'currentOrganization' => app(Roster::class)->organization($model),
            'currentTeam' => app(Roster::class)->team($model),
            'assignments' => app(ListRoleAssignmentsAction::class)->execute(new ListRoleAssignmentsData(user: $model, perPage: 100)),
            'effective' => app(ListUserPermissionsAction::class)->execute($model),
            // Shared roles, and the custom roles of this user's organizations:
            // another organization's own roles can't be assigned to them.
            'assignableRoles' => RoleModel::query()
                ->where(fn (Builder $query): Builder => $query->whereNull('organization_id')->orWhereIn('organization_id', $memberships->pluck('organization_id')->all()))
                ->with('organization')
                ->orderBy('scope')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function backTo(Model $user, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.users.show', $user->getRouteKey())
            ->with('status', __($message));
    }

    /**
     * A blank password field means "keep the current one" on edit and "set a
     * random one" on create, so it never reaches validation.
     */
    private function dropBlankPassword(Request $request): void
    {
        if (blank($request->input('password'))) {
            $request->request->remove('password');
        }
    }
}
