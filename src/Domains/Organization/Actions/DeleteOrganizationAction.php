<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationDeletedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationDeletingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class DeleteOrganizationAction
{
    /**
     * Delete an organization. It's a soft delete: everything it has is kept,
     * its slug and domains stay reserved, and it can be restored until it's
     * purged (PurgeOrganizationAction / roster:purge-deleted). A personal
     * organization goes only with its owner.
     */
    public function execute(OrganizationModel $organization, bool $force = false): void
    {
        if ($organization->personal && ! $force) {
            throw ValidationException::withMessages([
                'organization' => __('roster::roster.cannot_delete_personal'),
            ]);
        }

        OrganizationDeletingActionEvent::dispatch($organization);

        DB::transaction(fn () => $organization->delete());

        OrganizationDeletedActionEvent::dispatch($organization);
    }
}
