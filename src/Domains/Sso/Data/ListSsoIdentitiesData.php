<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Data;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;

/**
 * Input for ListSsoIdentitiesAction: the user (a model or its route key),
 * the organization slug, and the page to show.
 */
final readonly class ListSsoIdentitiesData extends Data
{
    public function __construct(
        public Model|int|string|null $user = null,
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
            'user' => ['sometimes', 'nullable'],
            'organization' => ['sometimes', 'nullable', 'string'],
        ] + self::pagination()->rules();
    }
}
