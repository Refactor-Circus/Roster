<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListScimTokensAction: the page to show.
 */
final readonly class ListScimTokensData extends Data
{
    public function __construct(
        public int $perPage = 25,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages(default: 25);
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
        ] + self::pagination()->rules();
    }
}
