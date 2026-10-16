<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Support\Concerns\PreparesStatuses;
use RefactorCircus\Roster\Support\Statuses;

/**
 * Input for ListOrganizationsAction: filters and the page to show.
 *
 * `trashed`: `only` lists deleted organizations only, `with` all of them.
 * `user` (a route key) limits the list to that user's organizations;
 * `source`, `external_id` and `account_number` find organizations by their
 * records in external systems. `status` lists organizations with that status.
 */
final readonly class ListOrganizationsData extends Data
{
    use PreparesStatuses;

    public function __construct(
        public ?string $search = null,
        public ?string $trashed = null,
        public int|string|null $user = null,
        public ?string $source = null,
        public ?string $externalId = null,
        public ?string $accountNumber = null,
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
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
            'trashed' => ['sometimes', 'nullable', Rule::in(['only', 'with'])],
            'user' => ['sometimes', 'nullable'],
            'source' => ['sometimes', 'nullable', 'string', 'max:64'],
            'external_id' => ['sometimes', 'nullable', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
            'status' => ['sometimes', 'nullable', Rule::enum(Statuses::organizations())],
        ] + self::pagination()->rules();
    }

    protected static function prepare(array $validated): array
    {
        return self::prepareStatuses($validated, ['status' => Statuses::organization(...)]);
    }
}
