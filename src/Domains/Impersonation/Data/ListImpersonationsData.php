<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListImpersonationsAction: filters and the page to show.
 *
 * `user` and `impersonator` are user route keys, `organization` an
 * organization slug; `active` keeps only impersonations still running.
 */
final readonly class ListImpersonationsData extends Data
{
    public function __construct(
        public bool $active = false,
        public int|string|null $user = null,
        public int|string|null $impersonator = null,
        public ?string $organization = null,
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
            'active' => ['sometimes', 'boolean'],
            'user' => ['sometimes', 'nullable'],
            'impersonator' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
        ] + self::pagination()->rules();
    }
}
