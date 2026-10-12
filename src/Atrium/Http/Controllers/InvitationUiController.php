<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Actions\RevokeInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class InvitationUiController
{
    use AuthorizesScreens;

    public function store(Request $request, string $organization): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.invitations.manage', $model);

        app(CreateInvitationAction::class)->execute(
            $model,
            CreateInvitationData::from($request->all()),
            $this->actor($request),
        );

        return $this->backTo($model, 'roster::roster.invitation_sent');
    }

    public function revoke(string $organization, string $invitation): RedirectResponse
    {
        $model = $this->organization($organization);
        $this->authorizeScreen('roster.invitations.manage', $model);

        app(RevokeInvitationAction::class)->execute($model->invitations()->whereKey($invitation)->firstOrFail());

        return $this->backTo($model, 'roster::roster.invitation_revoked');
    }

    private function backTo(OrganizationModel $organization, string $message): RedirectResponse
    {
        return redirect()
            ->route('atrium.roster.organizations.show', [$organization, 'tab' => 'invitations'])
            ->with('status', __($message));
    }
}
