<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Roster\Domains\Organization\Actions\PurgeOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * Purges a deleted organization in the background: a large one takes its
 * teams, memberships, invitations, roles and the rest with it, which is no
 * work for a request. Queued once per organization, after the request's
 * transaction commits; an organization restored or already purged by the
 * time it runs is left alone.
 */
final class PurgeOrganizationJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    /**
     * Gone already (purged by an earlier run): nothing left to do.
     */
    public bool $deleteWhenMissingModels = true;

    public function __construct(public OrganizationModel $organization)
    {
        $this->afterCommit();
    }

    public function uniqueId(): string
    {
        return (string) $this->organization->getKey();
    }

    public function handle(PurgeOrganizationAction $action): void
    {
        // Restored since it was queued: someone wants it back.
        if (! $this->organization->trashed()) {
            return;
        }

        $action->execute($this->organization);
    }
}
