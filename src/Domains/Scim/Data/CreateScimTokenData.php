<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateScimTokenAction: the token's name, its lifetime in days
 * (none: it never expires), and the slug of the organization's SSO
 * connection whose subjects provisioned users' externalIds become.
 */
final readonly class CreateScimTokenData extends Data
{
    public function __construct(
        public string $name,
        public ?int $expiresInDays = null,
        public ?string $ssoConnection = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'expires_in_days' => ['sometimes', 'nullable', 'integer', 'min:1', 'max:3650'],
            'sso_connection' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
