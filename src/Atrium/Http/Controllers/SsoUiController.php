<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Actions\DeleteSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Actions\ShowSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Actions\UnlinkSsoIdentityAction;
use RefactorCircus\Roster\Domains\Sso\Actions\UpdateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;

/**
 * Atrium: an organization's SSO connections, and unlinking identities.
 */
final class SsoUiController
{
    use AuthorizesScreens;

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.sso.manage', $model);
        $this->booleans($request);

        $connection = app(CreateSsoConnectionAction::class)->execute($model, CreateSsoConnectionData::from($request->all()));

        return redirect()
            ->route('atrium.roster.sso.show', $connection->slug)
            ->with('status', __('roster::roster.sso_connection_created'));
    }

    public function show(string $connection): View
    {
        $model = $this->find($connection);
        $this->authorizeScreen('roster.sso.view', $model->organization);

        /** @var view-string $view */
        $view = 'roster::ui.sso.show';

        return view($view, ['connection' => app(ShowSsoConnectionAction::class)->execute($model)]);
    }

    public function update(Request $request, string $connection): RedirectResponse
    {
        $model = $this->find($connection);
        $this->authorizeScreen('roster.sso.manage', $model->organization);
        $this->booleans($request);

        $model = app(UpdateSsoConnectionAction::class)->execute($model, UpdateSsoConnectionData::from($request->all(), $model));

        return redirect()
            ->route('atrium.roster.sso.show', $model->slug)
            ->with('status', __('roster::roster.sso_connection_updated'));
    }

    public function destroy(string $connection): RedirectResponse
    {
        $model = $this->find($connection);
        $this->authorizeScreen('roster.sso.manage', $model->organization);
        $organization = $model->organization;

        app(DeleteSsoConnectionAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'sso'])
            ->with('status', __('roster::roster.sso_connection_deleted'));
    }

    public function unlink(Request $request, string $identity): RedirectResponse
    {
        $model = SsoIdentityModel::query()->whereKey($identity)->firstOrFail();
        $actor = $request->user();
        $actor = $actor instanceof Model ? $actor : null;
        $this->authorizeScreen('roster.sso.manage', $model->connection?->organization, $model->ownedBy($actor) ? $actor : null);

        $user = $model->user;

        app(UnlinkSsoIdentityAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.users.show', $user instanceof Model ? $user->getRouteKey() : 0)
            ->with('status', __('roster::roster.sso_identity_unlinked'));
    }

    private function find(string $slug): SsoConnectionModel
    {
        return SsoConnectionModel::query()->where('slug', $slug)->firstOrFail();
    }

    /**
     * Unchecked boxes send nothing; treat them as false.
     */
    private function booleans(Request $request): void
    {
        foreach (['jit', 'enforced', 'enabled'] as $field) {
            $request->merge([$field => $request->boolean($field)]);
        }
    }
}
