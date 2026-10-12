<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListPermissionsAction: a name search and the page to show.
 */
final readonly class ListPermissionsData extends Data
{
    public function __construct(
        public ?string $search = null,
        public int $perPage = 50,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages(default: 50, max: 200);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:255'],
        ] + self::pagination()->rules();
    }
}
