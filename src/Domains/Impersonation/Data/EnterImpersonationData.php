<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Data;

use RefactorCircus\Keystone\Data\Data;
use SensitiveParameter;

/**
 * Input for EnterImpersonationAction: the token from the one-time link.
 */
final readonly class EnterImpersonationData extends Data
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'token' => ['required', 'string', 'max:255'],
        ];
    }
}
