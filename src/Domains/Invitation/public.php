<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Invitation\Http\Controllers\InvitationController;

// The public invitation lookup, loaded by InvitationServiceProvider inside the
// API prefix but with `roster.invitations.lookup_middleware` instead of the
// API's (authenticating) middleware: the token is the credential. Only
// well-formed tokens match, so a malformed one is a 404 before any query.

Route::get('invitations/{token}', [InvitationController::class, 'lookup'])
    ->where('token', '[A-Za-z0-9]{48}')
    ->name('invitations.lookup');
