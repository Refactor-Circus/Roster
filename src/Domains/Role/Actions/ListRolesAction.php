<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Role\Events\RolesListedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RolesListingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListRolesAction
{
    use ResolvesScopes;

    /**
     * Roles by scope and name. With `organization`, the roles usable in that
     * organization: the shared ones plus its own. Without, the shared ones.
     *
     * @param  array<int, int|string>|null  $organizations  Only these organizations; null for no limit.
     * @return LengthAwarePaginator<int, RoleModel>
     */
    public function execute(ListRolesData $filters = new ListRolesData, ?array $organizations = null): LengthAwarePaginator
    {
        RolesListingActionEvent::dispatch($filters);

        $organization = $this->organizationFrom($filters->organization);
        $scope = $filters->scope;

        $query = RoleModel::query()
            ->with(['permissions', 'organization'])
            // Shared roles, plus the organization's own - or, for a list limited to
            // some organizations, their own.
            ->where(fn (Builder $builder): Builder => match (true) {
                $organization !== null => $builder->whereNull('organization_id')->orWhere('organization_id', $organization->getKey()),
                $organizations !== null => $builder->whereNull('organization_id')->orWhereIn('organization_id', $organizations),
                default => $builder->whereNull('organization_id'),
            });

        if ($scope !== null) {
            $query->where('scope', $scope);
        }

        $roles = $query->orderBy('scope')->orderBy('name')->orderBy('id')->paginate($filters->perPage, ['*'], 'page', $filters->page);

        RolesListedActionEvent::dispatch($filters);

        return $roles;
    }
}
