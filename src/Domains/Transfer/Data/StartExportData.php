<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;

/**
 * Input for StartExportAction: the export type, the organization (a slug)
 * for organization-wide types, and the export's filters.
 */
final readonly class StartExportData extends Data
{
    public function __construct(
        public TransferType $type,
        public ?string $organization = null,
        public ExportFiltersData $filters = new ExportFiltersData,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in([TransferType::ExportMembers->value, TransferType::ExportUsers->value, TransferType::ExportOrganizations->value])],
            'organization' => ['sometimes', 'nullable', 'string'],
            'filters' => ['sometimes', 'array'],
            'filters.external_source' => ['sometimes', 'nullable', 'string', 'max:64'],
        ];
    }
}
