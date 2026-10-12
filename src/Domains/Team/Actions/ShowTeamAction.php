<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use RefactorCircus\Roster\Domains\Team\Events\TeamShowingActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamShownActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Tables;

final class ShowTeamAction
{
    /**
     * The most members a shown team carries, oldest first. Page through all
     * of them with ListMembersAction's `team` filter; `members_count` is the
     * full count.
     */
    public const int MEMBERS = 100;

    public function execute(TeamModel $team): TeamModel
    {
        TeamShowingActionEvent::dispatch($team);

        $memberships = Tables::name('memberships');

        // Deleted users keep their seats for a restore, but aren't shown.
        $team->load(['organization', 'memberships' => fn (BelongsToMany $query): BelongsToMany => $query
            ->whereHas('user')
            ->with('user')
            ->oldest($memberships.'.created_at')
            ->oldest($memberships.'.id')
            ->limit(self::MEMBERS),
        ])->loadCount('seats');

        TeamShownActionEvent::dispatch($team);

        return $team;
    }
}
