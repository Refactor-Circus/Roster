<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Impersonation\Actions\ListImpersonationsAction;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\ListImpersonationsData;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Support\Scopes;
use RefactorCircus\Roster\Support\Users;

/**
 * Atrium: start impersonating from a user's page (straight through the
 * one-time link), and review or end impersonations.
 */
final class ImpersonationUiController
{
    use AuthorizesScreens;

    public function __construct(private readonly Users $users) {}

    public function index(Request $request): View
    {
        $filters = ListImpersonationsData::from($request->query());
        $within = $this->authorizeList('roster.users.impersonate', Scopes::fromInput($filters->organization));

        $resolved = Atrium::screens()->resolve($this->screen('roster.impersonations', Screen::LIST)->tabs($this->overviewTab()), $request);

        /** @var view-string $view */
        $view = 'roster::ui.impersonations.index';

        return view($view, [
            'resolved' => $resolved,
            // The list only while it is the open tab. Each impersonation's
            // organization is loaded with it, so its row slot asks no query per row.
            ...match ($resolved->openTab) {
                Screen::OVERVIEW => ['impersonations' => app(ListImpersonationsAction::class)->execute($filters, $within)->withQueryString()],
                default => [],
            },
        ]);
    }

    public function start(Request $request, string $user): RedirectResponse
    {
        $this->authorizeScreen('roster.users.impersonate', Scopes::fromInput($request->input('organization')));
        $actor = $request->user();
        abort_unless($actor instanceof Model, 401);

        $started = app(StartImpersonationAction::class)->execute(
            $this->users->findOrFail($user),
            StartImpersonationData::from($request->all()),
            $actor,
        );

        return redirect()->to($started->url);
    }

    public function stop(Request $request, string $impersonation): RedirectResponse
    {
        $model = ImpersonationModel::query()->whereKey($impersonation)->firstOrFail();
        $actor = $request->user();
        $actor = $actor instanceof Model ? $actor : null;
        $this->authorizeScreen('roster.users.impersonate', $model->organization, $model->startedBy($actor) ? $actor : null);

        app(StopImpersonationAction::class)->execute($model, new StopImpersonationData(why: $model->endReasonFor($actor)));

        return redirect()
            ->route('atrium.roster.impersonations.index')
            ->with('status', __('roster::roster.impersonation_ended'));
    }
}
