<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for TransferOwnershipAction: the member (by route key) who becomes
 * the owner.
 */
final readonly class TransferOwnershipData extends Data
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
