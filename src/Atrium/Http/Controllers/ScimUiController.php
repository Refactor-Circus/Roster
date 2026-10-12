<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

/**
 * Atrium: issue and revoke an organization's SCIM tokens.
 */
final class ScimUiController
{
    use AuthorizesScreens;

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.scim.manage', $model);
        $issued = app(CreateScimTokenAction::class)->execute(
            $model,
            CreateScimTokenData::from($request->all()),
            $this->actor($request),
        );

        // Flashed for exactly one page view, then gone.
        return redirect()
            ->route('atrium.roster.organizations.show', [$model, 'tab' => 'scim'])
            ->with('roster_scim_token', $issued->plain)
            ->with('status', __('roster::roster.scim_token_created'));
    }

    public function revoke(string $token): RedirectResponse
    {
        $model = ScimTokenModel::query()->whereKey($token)->firstOrFail();
        $this->authorizeScreen('roster.scim.manage', $model->organization);

        app(RevokeScimTokenAction::class)->execute($model);

        return redirect()
            ->route('atrium.roster.organizations.show', [$model->organization, 'tab' => 'scim'])
            ->with('status', __('roster::roster.scim_token_revoked'));
    }
}
