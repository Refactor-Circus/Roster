<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationRestoredActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationRestoringActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class RestoreOrganizationAction
{
    /**
     * Bring back a deleted organization with everything it kept. Its slug and
     * domains stayed reserved, so nothing can clash.
     */
    public function execute(OrganizationModel $organization): OrganizationModel
    {
        if (! $organization->trashed()) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.not_deleted')]);
        }

        OrganizationRestoringActionEvent::dispatch($organization);

        DB::transaction(fn () => $organization->restore());

        $organization = $organization->load(['domains', 'links', 'owner'])->loadCount(['memberships', 'teams']);

        OrganizationRestoredActionEvent::dispatch($organization);

        return $organization;
    }
}
