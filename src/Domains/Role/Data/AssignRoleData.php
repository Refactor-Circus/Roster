<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * Input for AssignRoleAction: the role's id, and the organization and team
 * slugs that scope it (the organization may be given as the model, saving
 * a lookup). No organization gives a global role; a team needs
 * its organization.
 */
final readonly class AssignRoleData extends Data
{
    public function __construct(
        public string $role,
        public OrganizationModel|string|null $organization = null,
        public ?string $team = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'role' => ['required', 'string', Rule::exists(RoleModel::class, 'id')],
            'organization' => ['sometimes', 'nullable', 'string'],
            'team' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
