<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Concerns;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Support\Statuses;
use RefactorCircus\Roster\Support\Users;

/**
 * Moves a user between statuses, guarding the transitions every status
 * Action shares.
 */
trait ChangesStatus
{
    private function guardTransition(Model $user, AccountStatus $to, ?Model $actor): void
    {
        if ($actor !== null && $actor->is($user) && $to !== Statuses::users()::active()) {
            throw ValidationException::withMessages([
                'user' => __('roster::roster.cannot_change_own_status'),
            ]);
        }

        // A pending account is accepted with ApproveUserAction, on the record.
        if ($to === Statuses::users()::active() && app(Users::class)->status($user) === Statuses::users()::pending()) {
            throw ValidationException::withMessages(['status' => __('roster::roster.approve_instead')]);
        }

        if (app(Users::class)->status($user) === $to) {
            throw ValidationException::withMessages([
                'status' => __('roster::roster.already_status', ['status' => $to->value]),
            ]);
        }
    }

    /**
     * Approving and rejecting answer an account waiting for approval, and
     * nobody answers their own.
     */
    private function guardPending(Model $user, ?Model $actor): void
    {
        if (app(Users::class)->status($user) !== Statuses::users()::pending()) {
            throw ValidationException::withMessages(['user' => __('roster::roster.not_pending')]);
        }

        if ($actor !== null && $actor->is($user)) {
            throw ValidationException::withMessages(['user' => __('roster::roster.cannot_change_own_status')]);
        }
    }

    private function changeStatus(Model $user, AccountStatus $to, ?string $reason): void
    {
        app(Users::class)->profile($user)->update([
            'status' => $to,
            'status_reason' => $reason,
            'status_changed_at' => now(),
        ]);
    }
}
