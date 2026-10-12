<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Data;

use RefactorCircus\Keystone\Data\Data;
use SensitiveParameter;

/**
 * Input for DeclineInvitationAction: the token from the invitation link.
 */
final readonly class DeclineInvitationData extends Data
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
