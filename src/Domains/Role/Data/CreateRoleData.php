<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;

/**
 * Input for CreateRoleAction: the role and the permission names it grants.
 *
 * Without `slug` one is made from the name. With `organization` the role is
 * that organization's own (organization or team scope only).
 */
final readonly class CreateRoleData extends Data
{
    /**
     * @param  array<int, string>  $permissions
     */
    public function __construct(
        public string $name,
        public RoleScope $scope,
        public ?string $slug = null,
        public ?string $organization = null,
        public ?string $description = null,
        public array $permissions = [],
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['sometimes', 'nullable', 'string', 'alpha_dash', 'max:255'],
            'scope' => ['required', Rule::enum(RoleScope::class)],
            'organization' => ['sometimes', 'nullable', 'string'],
            'description' => ['sometimes', 'nullable', 'string', 'max:1000'],
            'permissions' => ['sometimes', 'array'],
            'permissions.*' => ['string', 'distinct', Rule::exists(PermissionModel::class, 'name')],
        ];
    }
}
