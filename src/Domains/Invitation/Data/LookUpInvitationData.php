<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Data;

use RefactorCircus\Keystone\Data\Data;
use SensitiveParameter;

/**
 * Input for LookUpInvitationAction: the token from the invitation link.
 *
 * The token is the only credential, so no event carries this object.
 */
final readonly class LookUpInvitationData extends Data
{
    public function __construct(
        #[SensitiveParameter]
        public string $token,
    ) {}

    /**
     * Invitation tokens are 48 alphanumeric characters (InvitationTokens).
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'token' => ['required', 'string', 'size:48', 'alpha_num:ascii'],
        ];
    }
}
