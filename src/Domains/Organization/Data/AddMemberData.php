<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for AddMemberAction: the user (by route key) to add.
 */
final readonly class AddMemberData extends Data
{
    public function __construct(
        public int|string $user,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'user' => ['required'],
        ];
    }
}
