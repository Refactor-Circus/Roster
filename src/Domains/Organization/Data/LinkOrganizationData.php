<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * Input for LinkOrganizationAction: the organization's id, and optionally
 * its account number, in one external system. An account number left out
 * keeps the one already linked.
 */
final readonly class LinkOrganizationData extends Data
{
    public function __construct(
        public string $source,
        public string $externalId,
        public string|Missing|null $accountNumber = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'source' => ['required', 'string', 'max:64', 'regex:/^[a-z0-9][a-z0-9_.-]*$/'],
            'external_id' => ['required', 'string', 'max:191'],
            'account_number' => ['sometimes', 'nullable', 'string', 'max:191'],
        ];
    }
}
