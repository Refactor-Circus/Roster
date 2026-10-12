<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Invitation\Data\ListInvitationsData;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationsListedActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationsListingActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

final class ListInvitationsAction
{
    /**
     * Newest first.
     *
     * @return LengthAwarePaginator<int, InvitationModel>
     */
    public function execute(OrganizationModel $organization, ListInvitationsData $filters = new ListInvitationsData): LengthAwarePaginator
    {
        InvitationsListingActionEvent::dispatch($organization, $filters);

        $query = $organization->invitations()->with('organization');

        $status = $filters->status;

        if ($status !== null) {
            $query->withStatus($status);
        }

        $invitations = $query->latest()->latest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        // The teams invitations name, for the whole page in one query.
        $teams = TeamModel::query()
            ->whereIn('id', $invitations->getCollection()->flatMap(fn (InvitationModel $invitation): array => (array) $invitation->teams)->unique()->all())
            ->get(['id', 'slug'])
            ->keyBy('id');

        $invitations->getCollection()->each(fn (InvitationModel $invitation) => $invitation->setRelation(
            'teamModels',
            $teams->only((array) $invitation->teams)->sortBy('slug')->values(),
        ));

        InvitationsListedActionEvent::dispatch($organization, $filters);

        return $invitations;
    }
}
