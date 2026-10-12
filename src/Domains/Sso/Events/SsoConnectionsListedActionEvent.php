<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoConnectionsData;

/**
 * SSO connections have been listed.
 */
final class SsoConnectionsListedActionEvent implements ActionFinishedEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public ListSsoConnectionsData $filters,
    ) {}
}
