<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Contracts;

use RefactorCircus\Roster\Data\ExternalSignIn;

/**
 * Decides whether an email must sign in somewhere else (single sign-on, an
 * identity provider) instead of with a password. Registered from a
 * provider's `boot()` with `Roster::requireSignIn()` and asked, in
 * registration order, by `Roster::externalSignInFor()`; requirements must
 * not claim the same emails.
 *
 * A requirement that throws fails the sign-in closed: the exception reaches
 * Laravel's handler, and the login or registration it guards is refused.
 */
interface SignInRequirement
{
    /**
     * The sign-in this email must use, or null when this requirement has
     * nothing to say about it.
     */
    public function for(string $email): ?ExternalSignIn;
}
