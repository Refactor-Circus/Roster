<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Events\TeamUpdatedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamUpdatingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

final class UpdateTeamAction
{
    public function execute(TeamModel $team, UpdateTeamData $data): TeamModel
    {
        TeamUpdatingActionEvent::dispatch($team, $data);

        $attributes = $data->toArray();

        if ($data->status instanceof Status && $data->status !== $team->status) {
            $attributes['status_changed_at'] = now();
        }

        DB::transaction(fn () => $team->update($attributes));

        $team = $team->refresh()->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamUpdatedActionEvent::dispatch($team);

        return $team;
    }
}
