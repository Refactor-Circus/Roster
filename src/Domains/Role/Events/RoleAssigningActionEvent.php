<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * A role is about to be assigned.
 */
final class RoleAssigningActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public Model $user,
        public RoleModel $role,
        public AssignRoleData $data,
    ) {}
}
