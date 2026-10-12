<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\User\Concerns\ChangesStatus;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UserReactivatedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserReactivatingActionEvent;

final class ReactivateUserAction
{
    use ChangesStatus;

    /**
     * Return a suspended or deactivated user to active, clearing the reason.
     */
    public function execute(Model $user, ?Model $actor = null): Model
    {
        $this->guardTransition($user, UserStatus::Active, $actor);

        UserReactivatingActionEvent::dispatch($user);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Active, null));

        $user = $user->load('rosterProfile');

        UserReactivatedActionEvent::dispatch($user);

        return $user;
    }
}
