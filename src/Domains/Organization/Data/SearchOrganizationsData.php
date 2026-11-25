<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for SearchOrganizationsAction: what an organization's name or slug
 * starts with (`q`, matched literally) and how many organizations to answer
 * at most.
 */
final readonly class SearchOrganizationsData extends Data
{
    public function __construct(
        public string $q,
        public int $limit = 5,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:1', 'max:100'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }
}
