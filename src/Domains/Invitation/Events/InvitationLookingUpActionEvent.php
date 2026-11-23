<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;

/**
 * An invitation is about to be looked up by its token.
 *
 * It carries nothing: the input is the token, the only credential an
 * invitation has, so it never reaches listeners or the audit log.
 */
final class InvitationLookingUpActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;
}
