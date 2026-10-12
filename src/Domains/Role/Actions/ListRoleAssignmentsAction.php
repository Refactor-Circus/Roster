<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Events\RoleAssignmentsListedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleAssignmentsListingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;
use RefactorCircus\Roster\Support\Users;

final class ListRoleAssignmentsAction
{
    use ResolvesScopes;

    public function __construct(private readonly Users $users) {}

    /**
     * Assignments for a user, an organization (including its teams), or both.
     *
     * @return LengthAwarePaginator<int, RoleAssignmentModel>
     */
    public function execute(ListRoleAssignmentsData $filters = new ListRoleAssignmentsData): LengthAwarePaginator
    {
        RoleAssignmentsListingActionEvent::dispatch($filters);

        $query = RoleAssignmentModel::query()->with(['role', 'user', 'organization', 'team.organization'])->whereHas('user');

        if ($filters->user !== null) {
            $query->where('user_id', $this->users->resolve($filters->user)->getKey());
        }

        $organization = $this->organizationFrom($filters->organization);

        if ($organization !== null) {
            $query->where('organization_id', $organization->getKey());
        }

        $assignments = $query->oldest()->oldest('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        RoleAssignmentsListedActionEvent::dispatch($filters);

        return $assignments;
    }
}
