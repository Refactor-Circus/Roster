<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\User\Models\UserModel;

/**
 * The UserModel `retrieved` Eloquent event.
 */
final class UserRetrievedEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public UserModel $user) {}

    public function model(): Model
    {
        return $this->user;
    }

    public function hook(): string
    {
        return 'retrieved';
    }
}
