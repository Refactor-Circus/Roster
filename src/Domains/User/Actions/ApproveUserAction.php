<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\User\Concerns\ChangesStatus;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Events\UserApprovedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserApprovingActionEvent;
use RefactorCircus\Roster\Domains\User\Services\Approvals;

final class ApproveUserAction
{
    use ChangesStatus;

    /**
     * Accept an account that is waiting for approval: it becomes active.
     */
    public function execute(Model $user, ?Model $actor = null): Model
    {
        $this->guardPending($user, $actor);

        UserApprovingActionEvent::dispatch($user);

        DB::transaction(fn () => $this->changeStatus($user, UserStatus::Active, null));

        $user = $user->load('rosterProfile');

        UserApprovedActionEvent::dispatch($user);

        app(Approvals::class)->approved($user);

        return $user;
    }
}
