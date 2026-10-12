<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * The RoleModel `retrieved` Eloquent event.
 */
final class RoleRetrievedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public RoleModel $role) {}

    public function model(): Model
    {
        return $this->role;
    }

    public function hook(): string
    {
        return 'retrieved';
    }
}
