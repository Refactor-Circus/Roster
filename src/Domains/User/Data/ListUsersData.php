<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;

/**
 * Input for ListUsersAction: filters and the page to show.
 *
 * `trashed`: `only` lists deleted users only, `with` everyone. It needs a
 * soft-deleting user model.
 */
final readonly class ListUsersData extends Data
{
    public function __construct(
        public ?string $search = null,
        public ?UserStatus $status = null,
        public ?string $trashed = null,
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
            'status' => ['sometimes', 'nullable', Rule::enum(UserStatus::class)],
            'trashed' => ['sometimes', 'nullable', Rule::in(['only', 'with'])],
        ] + self::pagination()->rules();
    }
}
