<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;

/**
 * Input for ListRolesAction: filters and the page to show. With
 * `organization`, the roles usable in it: the shared ones plus its own.
 */
final readonly class ListRolesData extends Data
{
    public function __construct(
        public ?RoleScope $scope = null,
        public OrganizationModel|string|null $organization = null,
        public int $perPage = 50,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages(default: 50);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'scope' => ['sometimes', 'nullable', Rule::enum(RoleScope::class)],
            'organization' => ['sometimes', 'nullable', 'string'],
        ] + self::pagination()->rules();
    }
}
