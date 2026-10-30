<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Support;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use RefactorCircus\Roster\Roster;

/**
 * For the app's password login form: fails when the email must sign in
 * elsewhere (an organization's enforced SSO, or any other registered
 * sign-in requirement), pointing the user at it. A requirement that throws
 * is not caught, so the login fails closed.
 *
 *     'email' => ['required', 'email', new NotSsoEnforced],
 */
final class NotSsoEnforced implements ValidationRule
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

        $fail(__('roster::roster.sso_required', [
            'organization' => $signIn->label,
            'url' => $signIn->url,
        ]));
    }
}
