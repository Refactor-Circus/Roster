<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for CreateInvitationAction: the email address to invite and the
 * slugs of the organization's teams to seat them on.
 */
final readonly class CreateInvitationData extends Data
{
    /**
     * @param  array<int, string>  $teams
     */
    public function __construct(
        public string $email,
        public array $teams = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'email' => ['required', 'email', 'max:255'],
            'teams' => ['sometimes', 'array', 'max:50'],
            'teams.*' => ['string', 'distinct'],
        ];
    }
}
