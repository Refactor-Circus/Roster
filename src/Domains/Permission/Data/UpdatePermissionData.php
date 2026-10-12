<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for UpdatePermissionAction: the new description; null clears it.
 */
final readonly class UpdatePermissionData extends Data
{
    public function __construct(
        public ?string $description,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'description' => ['present', 'nullable', 'string', 'max:1000'],
        ];
    }
}
