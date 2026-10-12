<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Data;

use Illuminate\Http\UploadedFile;
use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;

/**
 * Input for StartImportAction: the import type, the organization (a slug)
 * for organization-wide types, and the CSV as an upload (`file`) or as
 * text (`content`).
 */
final readonly class StartImportData extends Data
{
    public function __construct(
        public TransferType $type,
        public ?string $organization = null,
        public ?UploadedFile $file = null,
        public ?string $content = null,
    ) {}

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'type' => ['required', Rule::in([TransferType::ImportMembers->value, TransferType::ImportUsers->value, TransferType::ImportTeams->value, TransferType::ImportOrganizations->value])],
            'organization' => ['sometimes', 'nullable', 'string'],
            'file' => ['sometimes', 'file', 'max:'.(int) ceil((int) config('roster.transfers.max_bytes', 5242880) / 1024)],
            'content' => ['required_without:file', 'nullable', 'string', 'max:'.(int) config('roster.transfers.max_bytes', 5242880)],
        ];
    }
}
