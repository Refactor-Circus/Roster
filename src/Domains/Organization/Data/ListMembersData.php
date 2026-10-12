<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use Illuminate\Database\Query\Builder;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Input for ListMembersAction: the page of members to show, optionally only
 * those seated on one team (by its slug).
 */
final readonly class ListMembersData extends Data
{
    public function __construct(
        public int $perPage = 15,
        public ?int $page = null,
        public ?string $team = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages();
    }

    /**
     * Pass the organization so the team is checked within it.
     *
     * @return array<string, array<int, mixed>>
     */
    public static function rules(?OrganizationModel $organization = null): array
    {
        $team = Rule::exists(TeamModel::class, 'slug');

        if ($organization !== null) {
            $team->where(fn (Builder $query): Builder => $query->where('organization_id', $organization->getKey()));
        }

        return [
            'team' => ['sometimes', 'nullable', 'string', 'max:255', $team],
        ] + self::pagination()->rules();
    }
}
