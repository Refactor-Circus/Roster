<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;

/**
 * The user counts per status are about to be shown. The action takes no
 * input, so the event carries none.
 */
final class UserStatsShowingActionEvent implements ActionStartingEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;
}
