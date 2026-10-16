<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\User\Concerns\ChangesStatus;
use RefactorCircus\Roster\Domains\User\Data\RejectUserData;
use RefactorCircus\Roster\Domains\User\Events\UserRejectedActionEvent;
use RefactorCircus\Roster\Domains\User\Events\UserRejectingActionEvent;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Support\Statuses;

final class RejectUserAction
{
    use ChangesStatus;

    /**
     * Turn down an account that is waiting for approval. It's deactivated
     * with the reason, not deleted: the email stays taken, and it can be
     * reactivated later.
     */
    public function execute(Model $user, RejectUserData $data = new RejectUserData, ?Model $actor = null): Model
    {
        $this->guardPending($user, $actor);

        $reason = $data->reason;

        UserRejectingActionEvent::dispatch($user, $reason);

        DB::transaction(fn () => $this->changeStatus($user, Statuses::users()::deactivated(), $reason));

        $user = $user->load('rosterProfile');

        UserRejectedActionEvent::dispatch($user, $reason);

        app(Approvals::class)->rejected($user, $reason);

        return $user;
    }
}
