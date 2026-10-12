<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Actions;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Permission\Data\ListUserPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Events\UserPermissionsListedActionEvent;
use RefactorCircus\Roster\Domains\Permission\Events\UserPermissionsListingActionEvent;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Support\Concerns\ResolvesScopes;

final class ListUserPermissionsAction
{
    use ResolvesScopes;

    public function __construct(private readonly Permissions $permissions) {}

    /**
     * The permissions a user effectively holds: globally, or in an
     * organization or one of its teams.
     *
     * @return array{super_admin: bool, permissions: array<int, string>}
     */
    public function execute(Model $user, ListUserPermissionsData $filters = new ListUserPermissionsData): array
    {
        UserPermissionsListingActionEvent::dispatch($user, $filters);

        $organization = $this->organizationFrom($filters->organization);
        $team = $this->teamFrom($organization, $filters->team);

        $result = [
            'super_admin' => $this->permissions->isSuperAdmin($user),
            'permissions' => $this->permissions->for($user, $team ?? $organization),
        ];

        UserPermissionsListedActionEvent::dispatch($user, $filters);

        return $result;
    }
}
