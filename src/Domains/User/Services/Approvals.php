<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Services;

use Illuminate\Contracts\Config\Repository;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Notification;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Roster\Domains\User\Notifications\UserApprovedNotification;
use RefactorCircus\Roster\Domains\User\Notifications\UserAwaitingApprovalNotification;
use RefactorCircus\Roster\Domains\User\Notifications\UserRejectedNotification;
use RefactorCircus\Roster\Support\Users;

/**
 * Who approves new accounts, and the emails around approval. Each email is
 * switched by `roster.users.approvals.*`.
 */
final class Approvals
{
    public function __construct(
        private readonly Users $users,
        private readonly Repository $config,
        private readonly Roles $roles,
    ) {}

    /**
     * The users the bound role store says hold `roster.users.approve`
     * globally, super-admins included. None without a role store.
     *
     * @return Collection<int, Model>
     */
    public function approvers(): Collection
    {
        $keys = $this->roles->holders('roster.users.approve');

        return $keys === [] ? new Collection : $this->users->query()->whereKey($keys)->get();
    }

    /**
     * Someone is now waiting for approval: tell the approvers.
     */
    public function pending(Model $user): void
    {
        if ($this->config->get('roster.users.approvals.notify_approvers') !== true) {
            return;
        }

        foreach ($this->approvers() as $approver) {
            $email = $this->users->email($approver);

            if ($email !== null && ! $approver->is($user)) {
                Notification::route('mail', $email)->notify(new UserAwaitingApprovalNotification($user));
            }
        }
    }

    public function approved(Model $user): void
    {
        $this->tell($user, new UserApprovedNotification);
    }

    public function rejected(Model $user, ?string $reason): void
    {
        $this->tell($user, new UserRejectedNotification($reason));
    }

    private function tell(Model $user, UserApprovedNotification|UserRejectedNotification $notification): void
    {
        $email = $this->users->email($user);

        if ($email !== null && $this->config->get('roster.users.approvals.notify_user') === true) {
            Notification::route('mail', $email)->notify($notification);
        }
    }
}
