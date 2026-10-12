<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;

/**
 * The TeamMemberModel `deleted` Eloquent event.
 */
final class TeamMemberDeletedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TeamMemberModel $teamMember) {}

    public function model(): Model
    {
        return $this->teamMember;
    }

    public function hook(): string
    {
        return 'deleted';
    }
}
