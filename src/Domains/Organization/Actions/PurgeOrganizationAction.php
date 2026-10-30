<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationPurgedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationPurgingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Jobs\PurgeOrganizationJob;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

final class PurgeOrganizationAction
{
    /**
     * Delete an organization for good, with its teams, memberships,
     * invitations, roles and links. It must be
     * deleted (trashed) first; `$personal` is for purging a user's personal
     * organization along with them.
     */
    public function execute(OrganizationModel $organization, bool $personal = false): void
    {
        $this->guard($organization, $personal);

        OrganizationPurgingActionEvent::dispatch($organization);

        DB::transaction(function () use ($organization): void {
            // Not every host database enforces the nullOnDelete foreign keys
            // (SQLite without foreign_keys), so clear context explicitly.
            ProfileModel::query()
                ->where('current_organization_id', $organization->getKey())
                ->update(['current_organization_id' => null, 'current_team_id' => null]);

            $organization->forceDelete();
        });

        OrganizationPurgedActionEvent::dispatch($organization);
    }

    /**
     * Purge it in the background, as the API, MCP and Atrium do: it is
     * checked now, so a refusal still answers the caller, and removed by a
     * queued PurgeOrganizationJob once the request's transaction commits.
     * Its events fire when the job runs.
     */
    public function queue(OrganizationModel $organization): void
    {
        $this->guard($organization, false);

        PurgeOrganizationJob::dispatch($organization);
    }

    private function guard(OrganizationModel $organization, bool $personal): void
    {
        if ($organization->personal && ! $personal) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.cannot_delete_personal')]);
        }

        if (! $organization->trashed() && ! $personal) {
            throw ValidationException::withMessages(['organization' => __('roster::roster.delete_before_purge')]);
        }
    }
}
