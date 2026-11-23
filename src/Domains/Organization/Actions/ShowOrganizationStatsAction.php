<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Actions;

use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationStatsShowingActionEvent;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationStatsShownActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

final class ShowOrganizationStatsAction
{
    /**
     * How many organizations (deleted ones left out), teams and pending
     * invitations there are, counted in one query of three subqueries.
     *
     * @return array{organizations: int, teams: int, pending_invitations: int}
     */
    public function execute(): array
    {
        OrganizationStatsShowingActionEvent::dispatch();

        // A query with no table, on the organizations' connection.
        $counts = (array) OrganizationModel::query()->getQuery()->newQuery()
            ->selectSub(OrganizationModel::query()->toBase()->selectRaw('count(*)'), 'organizations')
            ->selectSub(TeamModel::query()->toBase()->selectRaw('count(*)'), 'teams')
            ->selectSub(InvitationModel::query()->pending()->toBase()->selectRaw('count(*)'), 'pending_invitations')
            ->first();

        $stats = [
            'organizations' => $this->integer($counts['organizations'] ?? 0),
            'teams' => $this->integer($counts['teams'] ?? 0),
            'pending_invitations' => $this->integer($counts['pending_invitations'] ?? 0),
        ];

        OrganizationStatsShownActionEvent::dispatch($stats);

        return $stats;
    }

    private function integer(mixed $count): int
    {
        return is_numeric($count) ? (int) $count : 0;
    }
}
