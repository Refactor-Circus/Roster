<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use SensitiveParameter;

/**
 * Emails an invitation link. Sent to the invited address, which need not
 * belong to a registered user yet.
 */
final class InvitationNotification extends Notification implements ShouldQueue
{
    use Queueable;

    /**
     * Queued, and only once the surrounding transaction commits: a rolled-back
     * change sends nothing, and a mail failure is retried by the queue rather
     * than failing the request.
     */
    public function __construct(
        public readonly InvitationModel $invitation,
        #[SensitiveParameter]
        public readonly string $token,
    ) {
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
        /** @var OrganizationModel $organization */
        $organization = $this->invitation->organization;

        return (new MailMessage)
            ->subject(__('roster::roster.invitation_subject', ['organization' => $organization->name]))
            ->line(__('roster::roster.invitation_line', ['organization' => $organization->name]))
            ->action(__('roster::roster.invitation_action'), $this->url())
            ->line(__('roster::roster.invitation_expires', ['date' => $this->invitation->expires_at->toDayDateTimeString()]));
    }

    /**
     * The accept link: the app's own page when `roster.invitations.accept_url`
     * is set, otherwise Roster's signed invitation page.
     */
    public function url(): string
    {
        $custom = config('roster.invitations.accept_url');

        if (is_string($custom) && $custom !== '') {
            return str_replace('{token}', $this->token, $custom);
        }

        return URL::temporarySignedRoute('roster.invitations.show', $this->invitation->expires_at, ['token' => $this->token]);
    }
}
