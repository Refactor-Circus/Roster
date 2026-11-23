<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\Roster\Domains\Organization\Data\SearchOrganizationsData;

/**
 * Organizations have been searched by the start of their name or slug. A
 * read: audit logs skip it.
 */
final class OrganizationsSearchedActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchOrganizationsData $data,
    ) {}
}
