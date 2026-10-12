<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;

/**
 * The PermissionModel `saved` Eloquent event.
 */
final class PermissionSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public PermissionModel $permission) {}

    public function model(): Model
    {
        return $this->permission;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
