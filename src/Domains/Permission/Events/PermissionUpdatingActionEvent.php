<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

/**
 * A permission is about to be updated.
 */
final class PermissionUpdatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public PermissionModel $permission,
        public UpdatePermissionData $data,
    ) {}
}
