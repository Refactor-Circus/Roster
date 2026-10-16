<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Support\Concerns\PreparesStatuses;
use RefactorCircus\Roster\Support\Statuses;

/**
 * Input for ListTeamsAction: filters and the page to show. `status` lists
 * teams with that status.
 */
final readonly class ListTeamsData extends Data
{
    use PreparesStatuses;

    public function __construct(
        public ?Status $status = null,
        public int $perPage = 15,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(Statuses::teams())],
        ] + self::pagination()->rules();
    }

    protected static function prepare(array $validated): array
    {
        return self::prepareStatuses($validated, ['status' => Statuses::team(...)]);
    }
}
