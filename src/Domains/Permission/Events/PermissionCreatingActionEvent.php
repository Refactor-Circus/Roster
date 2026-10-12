<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;

/**
 * A permission is about to be created.
 */
final class PermissionCreatingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public CreatePermissionData $data,
    ) {}
}
