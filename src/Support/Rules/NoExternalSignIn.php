<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RefactorCircus\Roster\Roster;

/**
 * For the app's password login form: fails when the email must sign in
 * elsewhere (a registered sign-in requirement claims it), pointing the user
 * at it. A requirement that throws is not caught, so the login fails closed.
 *
 *     'email' => ['required', 'email', new NoExternalSignIn],
 */
final class NoExternalSignIn implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value)) {
            return;
        }

        $signIn = app(Roster::class)->externalSignInFor($value);

        if ($signIn === null) {
            return;
        }

        $fail(__('roster::roster.external_sign_in_required', [
            'organization' => $signIn->label,
            'url' => $signIn->url,
        ]));
    }
}
