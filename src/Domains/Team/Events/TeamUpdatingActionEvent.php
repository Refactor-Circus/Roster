<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * A team is about to be updated.
 */
final class TeamUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TeamModel $team,
        public UpdateTeamData $data,
    ) {}
}
