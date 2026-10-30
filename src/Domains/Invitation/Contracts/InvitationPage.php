<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Contracts;

use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;

/**
 * The page an invitation email links to. Bound to the plain page by default
 * and to the Atrium page while Atrium is installed; an application may bind
 * its own.
 */
interface InvitationPage
{
    /**
     * Render the invitation, with the token its accept and decline forms post.
     */
    public function render(InvitationModel $invitation, string $token, Request $request): View;
}
