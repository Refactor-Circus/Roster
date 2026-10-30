<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Domains\Invitation\Actions\AcceptInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Actions\DeclineInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Data\AcceptInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Data\DeclineInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Exceptions\InvalidInvitationException;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;

/**
 * The page an invitation email links to, for the invited user rather than
 * an admin. Answering acts as the signed-in user, whose email must match.
 */
final class InvitationWebController
{
    public function show(Request $request, string $token): View
    {
        return app(InvitationPage::class)->render($this->find($token), $token, $request);
    }

    public function accept(Request $request, string $token): RedirectResponse
    {
        $invitation = app(AcceptInvitationAction::class)->execute(AcceptInvitationData::from(['token' => $token]), $this->user($request));

        return redirect((string) config('roster.invitations.redirect', '/'))
            ->with('status', __('roster::roster.invitation_accepted_flash', ['organization' => $invitation->organization?->name]));
    }

    public function decline(Request $request, string $token): RedirectResponse
    {
        app(DeclineInvitationAction::class)->execute(DeclineInvitationData::from(['token' => $token]), $this->user($request));

        return redirect((string) config('roster.invitations.redirect', '/'))
            ->with('status', __('roster::roster.invitation_declined_flash'));
    }

    private function find(string $token): InvitationModel
    {
        try {
            return InvitationTokens::find($token)->load('organization');
        } catch (InvalidInvitationException) {
            abort(404);
        }
    }

    private function user(Request $request): Model
    {
        $user = $request->user();

        abort_unless($user instanceof Model, 401);

        return $user;
    }
}
