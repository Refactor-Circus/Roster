<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Tells someone their account wasn't approved, with the reason when given.
 */
final class UserRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Queued, and only once the surrounding transaction commits: a rolled-back
     * change sends nothing, and a mail failure is retried by the queue rather
     * than failing the request.
     */
    public function __construct(public readonly ?string $reason = null)
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
        $message = (new MailMessage)
            ->subject(__('roster::roster.rejected_subject', ['app' => (string) config('app.name')]))
            ->line(__('roster::roster.rejected_line', ['app' => (string) config('app.name')]));

        return $this->reason === null || $this->reason === '' ? $message : $message->line(__('roster::roster.reason').': '.$this->reason);
    }
}
