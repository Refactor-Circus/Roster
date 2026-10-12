<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An organization is about to be updated.
 */
final class OrganizationUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        public UpdateOrganizationData $data,
    ) {}
}
