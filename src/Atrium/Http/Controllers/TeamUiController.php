<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Atrium\ScreenAccess;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Data\ListMembersData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\ShowTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class TeamUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.teams.manage', $model);

        $team = app(CreateTeamAction::class)->execute($model, CreateTeamData::from($request->all(), $model));

        return redirect()
            ->route('atrium.roster.teams.show', [$model, $team->slug])
            ->with('status', __('roster::roster.team_created'));
    }

    public function show(Request $request, string $organization, string $team): View
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.view', $model);

        $model = app(ShowTeamAction::class)->execute($model);

        /** @var OrganizationModel $owner */
        $owner = $model->organization;

        /** @var view-string $view */
        $view = 'roster::ui.teams.show';

        return view($view, [
            'team' => $model,
            'organization' => $owner,
            // Every member, a page at a time; the shown team carries only the first ones.
            'members' => app(ListMembersAction::class)
                ->execute($owner, new ListMembersData(perPage: 25, page: $request->integer('page', 1), team: $model->slug))
                ->withQueryString(),
            // The add-member picker, only for those who may add.
            'candidates' => ScreenAccess::allows('roster.teams.manage', $model) ? $this->candidates($owner) : [],
            'directory' => $this->users,
        ]);
    }

    public function update(Request $request, string $organization, string $team): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model);

        $model = app(UpdateTeamAction::class)->execute($model, UpdateTeamData::from($request->all(), $model));

        return $this->backTo($model, 'roster::roster.team_updated');
    }

    public function destroy(string $organization, string $team): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model->organization);

        app(DeleteTeamAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'teams'])
            ->with('status', __('roster::roster.team_deleted'));
    }

    public function addMember(Request $request, string $organization, string $team): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model);

        $model = app(AddTeamMemberAction::class)->execute($model, AddTeamMemberData::from($request->all()));

        return $this->backTo($model, 'roster::roster.team_member_added');
    }

    public function removeMember(string $organization, string $team, string $user): RedirectResponse
    {
        $model = $this->team($organization, $team);
        $this->authorizeScreen('roster.teams.manage', $model);

        $model = app(RemoveTeamMemberAction::class)->execute($model, $this->users->findOrFail($user));

        return $this->backTo($model, 'roster::roster.team_member_removed');
    }

    /**
     * The organization's first 100 members, by route key and display name.
     *
     * @return array<int|string, string>
     */
    private function candidates(OrganizationModel $organization): array
    {
        $candidates = [];

        $memberships = $organization->memberships()->whereHas('user')->with('user')->oldest()->oldest('id')->limit(100)->get();

        foreach ($memberships as $membership) {
            $user = $membership->user;

            if ($user instanceof Model) {
                $key = $user->getRouteKey();

                if (is_int($key) || is_string($key)) {
                    $candidates[$key] = $this->users->name($user) ?? $this->users->email($user) ?? (string) $key;
                }
            }
        }

        return $candidates;
    }

    private function team(string $organization, string $slug): TeamModel
    {
        return TeamModel::query()
            ->where('organization_id', $this->organization($organization)->getKey())
            ->where('slug', $slug)
            ->firstOrFail();
    }

    private function backTo(TeamModel $team, string $message): RedirectResponse
    {
        /** @var OrganizationModel $organization */
        $organization = $team->organization;

        return redirect()
            ->route('atrium.roster.teams.show', [$organization, $team->slug])
            ->with('status', __($message));
    }
}
