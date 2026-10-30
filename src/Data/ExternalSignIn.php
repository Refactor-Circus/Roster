<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Data;

use Illuminate\Database\Eloquent\Model;

/**
 * The sign-in an email must use instead of a password, as a
 * `SignInRequirement` answers it: a stable `key` for the sign-in, the `label`
 * people see (who requires it), the `url` where the sign-in starts, and the
 * record behind it, if any (the SSO connection, for Roster's own).
 */
final readonly class ExternalSignIn
{
    public function __construct(
        public string $key,
        public string $label,
        public string $url,
        public ?Model $record = null,
    ) {}
}
