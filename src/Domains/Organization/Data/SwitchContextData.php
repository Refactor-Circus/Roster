<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for SwitchContextAction: the organization (by slug) and optionally
 * one of its teams (by slug) to make current.
 */
final readonly class SwitchContextData extends Data
{
    public function __construct(
        public string $organization,
        public ?string $team = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'organization' => ['required', 'string'],
            'team' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
