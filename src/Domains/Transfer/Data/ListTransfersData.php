<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;

/**
 * Input for ListTransfersAction: filters and the page to show.
 *
 * `organization` is a slug: that organization's transfers instead of the
 * requester's own.
 */
final readonly class ListTransfersData extends Data
{
    public function __construct(
        public ?string $organization = null,
        public ?TransferType $type = null,
        public ?TransferStatus $status = null,
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
            'organization' => ['sometimes', 'nullable', 'string'],
            'type' => ['sometimes', 'nullable', Rule::enum(TransferType::class)],
            'status' => ['sometimes', 'nullable', Rule::enum(TransferStatus::class)],
        ] + self::pagination()->rules();
    }
}
