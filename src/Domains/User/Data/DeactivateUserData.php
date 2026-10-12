<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for DeactivateUserAction: why, for the user's record.
 */
final readonly class DeactivateUserData extends Data
{
    public function __construct(
        public ?string $reason = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'reason' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
