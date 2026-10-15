<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\User\Concerns\ChangesStatus;
use RefactorCircus\Roster\Domains\User\Data\DeactivateUserData;
use RefactorCircus\Roster\Domains\User\Events\UserDeactivatedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserDeactivatingActionEvent;
use RefactorCircus\Roster\Support\Statuses;

final class DeactivateUserAction
{
    use ChangesStatus;

    /**
     * Deactivate a user: the account is closed but kept, and can be
     * reactivated.
     */
    public function execute(Model $user, DeactivateUserData $data = new DeactivateUserData, ?Model $actor = null): Model
    {
        $this->guardTransition($user, Statuses::users()::deactivated(), $actor);

        $reason = $data->reason;

        UserDeactivatingActionEvent::dispatch($user, $reason);

        DB::transaction(fn () => $this->changeStatus($user, Statuses::users()::deactivated(), $reason));

        $user = $user->load('rosterProfile');

        UserDeactivatedActionEvent::dispatch($user, $reason);

        return $user;
    }
}
