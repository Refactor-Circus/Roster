<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Data\ListTeamsData;
use RefactorCircus\Roster\Domains\Team\Events\TeamsListedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamsListingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

final class ListTeamsAction
{
    /**
     * @return LengthAwarePaginator<int, TeamModel>
     */
    public function execute(OrganizationModel $organization, ListTeamsData $filters = new ListTeamsData): LengthAwarePaginator
    {
        TeamsListingActionEvent::dispatch($organization, $filters);

        $query = $organization->teams()->with('organization')->withCount('seats');

        if ($filters->status !== null) {
            $query->where('status', $filters->status);
        }

        $teams = $query
            ->orderBy('name')
            ->orderBy('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        TeamsListedActionEvent::dispatch($organization, $filters);

        return $teams;
    }
}
