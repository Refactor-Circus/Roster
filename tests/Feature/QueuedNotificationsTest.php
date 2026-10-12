<?php

declare(strict_types=1);

use Illuminate\Notifications\Events\NotificationSent;
use Illuminate\Notifications\SendQueuedNotifications;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Notifications\UserAwaitingApprovalNotification;

beforeEach(function (): void {
    grant(user(['email' => 'approver@example.com']), 'super-admin');
});

it('emails approvers only once the account is committed', function (): void {
    $sent = [];
    Event::listen(NotificationSent::class, function (NotificationSent $event) use (&$sent): void {
        $sent[] = $event->notification::class;
    });

    try {
        // An outer transaction that rolls back, as a failed SCIM call does.
        DB::transaction(function (): void {
            app(CreateUserAction::class)->execute(new CreateUserData(email: 'pat@example.com', name: 'Pat', status: UserStatus::Pending));

            throw new RuntimeException('rolled back');
        });
    } catch (RuntimeException) {
        //
    }

    expect($sent)->toBe([]);

    DB::transaction(fn () => app(CreateUserAction::class)->execute(new CreateUserData(email: 'pat@example.com', name: 'Pat', status: UserStatus::Pending)));

    expect($sent)->toBe([UserAwaitingApprovalNotification::class]);
});

it('queues the invitation email instead of sending it in the request', function (): void {
    Queue::fake();

    app(CreateInvitationAction::class)->execute(organization(), new CreateInvitationData(email: 'ada@example.com'));

    Queue::assertPushed(SendQueuedNotifications::class, 1);
});
