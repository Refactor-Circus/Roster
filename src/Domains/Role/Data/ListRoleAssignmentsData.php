<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Data;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Input for ListRoleAssignmentsAction: the user (a model or its route key),
 * the organization (including its teams), and the page to show.
 */
final readonly class ListRoleAssignmentsData extends Data
{
    public function __construct(
        public Model|int|string|null $user = null,
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
            'user' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
        ] + self::pagination()->rules();
    }
}
