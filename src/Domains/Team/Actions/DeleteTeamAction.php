<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Team\Events\TeamDeletedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamDeletingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

final class DeleteTeamAction
{
    public function execute(TeamModel $team): void
    {
        TeamDeletingActionEvent::dispatch($team);

        DB::transaction(function () use ($team): void {
            ProfileModel::query()->where('current_team_id', $team->getKey())->update(['current_team_id' => null]);

            $team->seats()->delete();
            $team->delete();
        });

        TeamDeletedActionEvent::dispatch($team);
    }
}
