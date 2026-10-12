<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\User\Concerns\ChangesStatus;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UserSuspendedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserSuspendingActionEvent;

final class SuspendUserAction
{
    use ChangesStatus;

    /**
     * Suspend a user: a temporary block, lifted by reactivating.
     */
    public function execute(Model $user, SuspendUserData $data = new SuspendUserData, ?Model $actor = null): Model
    {
        $this->guardTransition($user, UserStatus::Suspended, $actor);

        $reason = $data->reason;

        UserSuspendingActionEvent::dispatch($user, $reason);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Suspended, $reason));

        $user = $user->load('rosterProfile');

        UserSuspendedActionEvent::dispatch($user, $reason);

        return $user;
    }
}
