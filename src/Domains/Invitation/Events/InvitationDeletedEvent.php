<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;

/**
 * The InvitationModel `deleted` Eloquent event.
 */
final class InvitationDeletedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public InvitationModel $invitation) {}

    public function model(): Model
    {
        return $this->invitation;
    }

    public function hook(): string
    {
        return 'deleted';
    }
}
