<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells someone their account has been approved.
 */
final class UserApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Queued, and only once the surrounding transaction commits: a rolled-back
     * change sends nothing, and a mail failure is retried by the queue rather
     * than failing the request.
     */
    public function __construct()
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
        return (new MailMessage)
            ->subject(__('roster::roster.approved_subject', ['app' => (string) config('app.name')]))
            ->line(__('roster::roster.approved_line', ['app' => (string) config('app.name')]))
            ->action(__('roster::roster.approved_action'), url('/'));
    }
}
