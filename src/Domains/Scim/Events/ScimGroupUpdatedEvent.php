<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimGroupModel;

/**
 * The ScimGroupModel `updated` Eloquent event.
 */
final class ScimGroupUpdatedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ScimGroupModel $scimGroup) {}

    public function model(): Model
    {
        return $this->scimGroup;
    }

    public function hook(): string
    {
        return 'updated';
    }
}
