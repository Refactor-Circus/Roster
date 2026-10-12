<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Support\Users;

/**
 * Tells an approver that someone is waiting for their account to be
 * accepted, with a link to their page in Atrium when it's installed.
 */
final class UserAwaitingApprovalNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Queued, and only once the surrounding transaction commits: a rolled-back
     * change sends nothing, and a mail failure is retried by the queue rather
     * than failing the request.
     */
    public function __construct(public readonly Model $user)
    {
        $this->afterCommit();
    }

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $users = app(Users::class);
        $who = $users->name($this->user) ?? $users->email($this->user) ?? (string) $this->user->getRouteKey();

        $message = (new MailMessage)
            ->subject(__('roster::roster.awaiting_approval_subject', ['user' => $who]))
            ->line(__('roster::roster.awaiting_approval_line', ['user' => $who, 'email' => (string) $users->email($this->user)]));

        return Route::has('atrium.roster.users.show')
            ? $message->action(__('roster::roster.awaiting_approval_action'), route('atrium.roster.users.show', $this->user->getRouteKey()))
            : $message;
    }
}
