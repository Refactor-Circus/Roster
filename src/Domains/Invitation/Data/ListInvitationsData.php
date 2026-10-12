<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Data;

use Illuminate\Validation\Rule;
use RefactorCircus\Keystone\Data\Data;
use RefactorCircus\Keystone\Pagination\Pagination;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;

/**
 * Input for ListInvitationsAction: the status to show and the page.
 */
final readonly class ListInvitationsData extends Data
{
    public function __construct(
        public ?InvitationStatus $status = null,
        public int $perPage = 15,
        public ?int $page = null,
    ) {}

    /**
     * How the list pages; its tool states the same limits.
     */
    public static function pagination(): Pagination
    {
        return Pagination::pages();
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    public static function rules(): array
    {
        return [
            'status' => ['sometimes', 'nullable', Rule::enum(InvitationStatus::class)],
        ] + self::pagination()->rules();
    }
}
