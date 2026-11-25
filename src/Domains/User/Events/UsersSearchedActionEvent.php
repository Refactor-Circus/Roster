<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\ReadActionEvent;
use RefactorCircus\Roster\Domains\User\Data\SearchUsersData;

/**
 * Users have been searched by the start of their name or email. A read:
 * audit logs skip it, and the cached status counts stay.
 */
final class UsersSearchedActionEvent implements ActionFinishedEvent, ReadActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public SearchUsersData $data,
    ) {}
}
