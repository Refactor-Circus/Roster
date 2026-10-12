<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

/**
 * Input for CreatePermissionAction: the permission's dotted name, e.g.
 * `invoices.edit`, and what it allows.
 */
final readonly class CreatePermissionData extends Data
{
    public function __construct(
        public string $name,
        public ?string $description = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9_-]+(\.[a-z0-9_-]+)*$/', Rule::unique(PermissionModel::class, 'name')],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
