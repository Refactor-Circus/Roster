<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * The TeamModel `saving` Eloquent event.
 */
final class TeamSavingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TeamModel $team) {}

    public function model(): Model
    {
        return $this->team;
    }

    public function hook(): string
    {
        return 'saving';
    }
}
