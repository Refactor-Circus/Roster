<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimUserModel;

/**
 * The ScimUserModel `saved` Eloquent event.
 */
final class ScimUserSavedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public ScimUserModel $scimUser) {}

    public function model(): Model
    {
        return $this->scimUser;
    }

    public function hook(): string
    {
        return 'saved';
    }
}
