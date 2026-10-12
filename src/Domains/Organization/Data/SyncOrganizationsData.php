<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Data;

use RefactorCircus\Keystone\Data\Data;

/**
 * Input for SyncOrganizationsAction: a batch of sync records. Each record is
 * checked on its own (as SyncOrganizationData) by the Action, so one bad
 * record doesn't reject the batch.
 */
final readonly class SyncOrganizationsData extends Data
{
    /**
     * @param  list<array<string, mixed>>  $records
     */
    public function __construct(
        public array $records,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'records' => ['required', 'array', 'list', 'min:1', 'max:'.(int) config('roster.organizations.sync_batch', 500)],
            'records.*' => ['array'],
        ];
    }
}
