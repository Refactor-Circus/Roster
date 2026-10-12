<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Invitation\Data\ListInvitationsData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

/**
 * An organization's invitations are about to be listed.
 */
final class InvitationsListingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public OrganizationModel $organization,
        public ListInvitationsData $filters,
    ) {}
}
