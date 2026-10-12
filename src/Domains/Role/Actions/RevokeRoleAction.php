<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Concerns\GuardsEscalation;
use RefactorCircus\Roster\Domains\Role\Events\RoleRevokedActionEvent;
use RefactorCircus\Roster\Domains\Role\Events\RoleRevokingActionEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

final class RevokeRoleAction
{
    use GuardsEscalation;

    /**
     * Take a role away. Like granting, revoking needs the actor to hold the
     * role's permissions in that scope - no one can strip a super-admin.
     */
    public function execute(RoleAssignmentModel $assignment, ?Model $actor = null): void
    {
        $assignment->loadMissing(['role.permissions', 'organization', 'team']);

        /** @var RoleModel $role */
        $role = $assignment->role;

        if ($role->super && $actor !== null && app(Authorizer::class)->enabled() && ! app(Permissions::class)->isSuperAdmin($actor)) {
            throw ValidationException::withMessages(['role' => __('roster::roster.only_super_admins')]);
        }

        $this->guardEscalation($actor, $role->permissions->pluck('name')->all(), $assignment->team ?? $assignment->organization, 'role');

        RoleRevokingActionEvent::dispatch($assignment);

        DB::transaction(fn () => $assignment->delete());

        $user = $assignment->getAttribute('user_id');
        is_int($user) || is_string($user) ? app(Permissions::class)->flushUser($user) : app(Permissions::class)->flush();

        RoleRevokedActionEvent::dispatch($assignment);
    }
}
