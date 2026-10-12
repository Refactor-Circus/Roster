<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;

/**
 * The RoleAssignmentModel `created` Eloquent event.
 */
final class RoleAssignmentCreatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RoleAssignmentModel $roleAssignment) {}

    public function model(): Model
    {
        return $this->roleAssignment;
    }

    public function hook(): string
    {
        return 'created';
    }
}
