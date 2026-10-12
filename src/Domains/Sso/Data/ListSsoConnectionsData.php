<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Input for ListSsoConnectionsAction: the organization (a model or its
 * slug) and the page to show.
 */
final readonly class ListSsoConnectionsData extends Data
{
    public function __construct(
        public OrganizationModel|string|null $organization = null,
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
            'organization' => ['sometimes', 'nullable', 'string'],
        ] + self::pagination()->rules();
    }
}
