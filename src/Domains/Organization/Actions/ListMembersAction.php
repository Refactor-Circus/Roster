<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Organization\Data\ListMembersData;
use RefactorCircus\Roster\Domains\Organization\Events\MembersListedActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\MembersListingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;

final class ListMembersAction
{
    /**
     * @return LengthAwarePaginator<int, MembershipModel>
     */
    public function execute(OrganizationModel $organization, ListMembersData $filters = new ListMembersData): LengthAwarePaginator
    {
        MembersListingActionEvent::dispatch($organization, $filters);

        $members = $organization->memberships()
            ->with(['user.rosterProfile', 'teams', 'organization'])
            // Deleted users stay members (so a restore is complete) but aren't listed.
            ->whereHas('user')
            ->when($filters->team !== null, fn (Builder $query): Builder => $query->whereHas('teams', fn (Builder $teams): Builder => $teams->where('slug', $filters->team)))
            ->oldest()
            ->oldest('id')
            ->paginate($filters->perPage, ['*'], 'page', $filters->page);

        MembersListedActionEvent::dispatch($organization, $filters);

        return $members;
    }
}
