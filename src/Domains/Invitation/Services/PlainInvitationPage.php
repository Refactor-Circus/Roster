<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Services;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * The invitation page as plain HTML (`roster::invitations.show`), for
 * applications without Atrium.
 */
final class PlainInvitationPage implements InvitationPage
{
    public function render(InvitationModel $invitation, string $token, Request $request): View
    {
        /** @var view-string $view */
        $view = 'roster::invitations.show';

        return view($view, self::data($invitation, $token));
    }

    /**
     * What every invitation page receives: the invitation, its organization,
     * the names of the teams it adds the invitee to (sorted) and the token.
     *
     * @internal Shared with Roster's Atrium page, so both render the same data.
     *
     * @return array<string, mixed>
     */
    public static function data(InvitationModel $invitation, string $token): array
    {
        return [
            'invitation' => $invitation,
            'organization' => $invitation->organization,
            'teams' => TeamModel::query()->whereIn('id', (array) $invitation->teams)->orderBy('name')->pluck('name'),
            'token' => $token,
        ];
    }
}
