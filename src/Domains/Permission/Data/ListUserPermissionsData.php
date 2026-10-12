<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for ListUserPermissionsAction: the organization and team slugs to
 * check in. Neither means globally; a team needs its organization.
 */
final readonly class ListUserPermissionsData extends Data
{
    public function __construct(
        public ?string $organization = null,
        public ?string $team = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'organization' => ['sometimes', 'nullable', 'string'],
            'team' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
