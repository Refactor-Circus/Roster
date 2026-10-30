<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Atrium\Domains\Screens\Data\Screen;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\Http\Controllers\Concerns\AuthorizesScreens;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage as InvitationPageContract;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\PlainInvitationPage;

/**
 * The invitation page as Atrium's guest page (`roster::ui.invitations.show`),
 * declared to Atrium's registry as the `roster.invitation` detail screen so
 * packages can add tabs and sections to it.
 */
final class InvitationPage implements InvitationPageContract
{
    use AuthorizesScreens;

    public function render(InvitationModel $invitation, string $token, Request $request): View
    {
        $resolved = Atrium::screens()->resolve(
            $this->screen('roster.invitation', Screen::DETAIL, $invitation)->tabs($this->overviewTab()),
            $request,
        );

        /** @var view-string $view */
        $view = 'roster::ui.invitations.show';

        return view($view, ['resolved' => $resolved, ...PlainInvitationPage::data($invitation, $token)]);
    }
}
