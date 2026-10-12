<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships;
use RefactorCircus\Roster\Domains\Team\Events\TeamMemberRemovedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamMemberRemovingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

final class RemoveTeamMemberAction
{
    use ManagesMemberships;

    public function execute(TeamModel $team, Model $user): TeamModel
    {
        $seat = TeamMemberModel::query()
            ->where('team_id', $team->getKey())
            ->whereHas('membership', fn (Builder $query): Builder => $query->where('user_id', $user->getKey()))
            ->first()
            ?? throw ValidationException::withMessages(['user' => __('roster::roster.not_on_team')]);

        TeamMemberRemovingActionEvent::dispatch($team, $user);

        DB::transaction(function () use ($seat, $user, $team): void {
            $seat->delete();
            $this->revokeRoles($user, team: $team);
            $this->forgetContext($user, team: $team);
        });

        $team->load(['organization', 'memberships.user'])->loadCount('seats');

        TeamMemberRemovedActionEvent::dispatch($team, $user);

        return $team;
    }
}
