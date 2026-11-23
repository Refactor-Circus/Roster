<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\Roster\Domains\Organization\Data\SearchOrganizationsData;

/**
 * Organizations are about to be searched by the start of their name or
 * slug. A read: audit logs skip it.
 */
final class OrganizationsSearchingActionEvent implements ActionStartingEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchOrganizationsData $data,
    ) {}
}
