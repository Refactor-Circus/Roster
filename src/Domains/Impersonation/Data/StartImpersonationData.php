<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for StartImpersonationAction: why, for the record, and optionally the
 * organization (by slug) the impersonation is limited to.
 */
final readonly class StartImpersonationData extends Data
{
    public function __construct(
        public string $reason,
        public ?string $organization = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'reason' => ['required', 'string', 'min:3', 'max:500'],
            'organization' => ['sometimes', 'nullable', 'string'],
        ];
    }
}
