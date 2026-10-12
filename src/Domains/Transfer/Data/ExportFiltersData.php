<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Data;

use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Data\Missing;

/**
 * StartExportData's `filters`. An organizations export keeps the ones that
 * were given on the transfer: `external_source` limits it to organizations
 * synced from that source.
 */
final readonly class ExportFiltersData extends Data
{
    public function __construct(
        public string|Missing|null $externalSource = new Missing,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'external_source' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
