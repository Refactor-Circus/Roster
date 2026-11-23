<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Registered;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\User\Actions\ApproveUserAction;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\ReactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\RejectUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Data\RejectUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\RejectUserTool;
use RefactorCircus\Roster\Domains\User\Notifications\UserApprovedNotification;
use RefactorCircus\Roster\Domains\User\Notifications\UserAwaitingApprovalNotification;
use RefactorCircus\Roster\Domains\User\Notifications\UserRejectedNotification;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;
use Workbench\App\Models\User;

function pendingUser(array $attributes = []): User
{
    /** @var User */
    return app(CreateUserAction::class)->execute(CreateUserData::from($attributes + ['name' => 'Pat', 'email' => 'pat@example.com', 'status' => 'pending']));
}

beforeEach(function (): void {
    Notification::fake();
    $this->approver = user(['email' => 'approver@example.com']);
    grant($this->approver, 'super-admin');
});

it('creates users as pending when asked, and tells the approvers', function (): void {
    $user = pendingUser();

    expect(app(Users::class)->status($user))->toBe(UserStatus::Pending)
        ->and(app(Users::class)->status(app(CreateUserAction::class)->execute(new CreateUserData(name: 'Al', email: 'al@example.com'))))->toBe(UserStatus::Active);

    Notification::assertSentOnDemand(UserAwaitingApprovalNotification::class, fn ($notification, $channels, $notifiable): bool => $notifiable->routes['mail'] === 'approver@example.com');
});

it('sends the approvers\' email without a button while roster.users.approvals.review_url is not set', function (): void {
    expect(config('roster.users.approvals.review_url'))->toBeNull();

    $mail = (new UserAwaitingApprovalNotification(pendingUser()))->toMail(new AnonymousNotifiable);

    expect($mail->actionUrl)->toBeNull()
        ->and($mail->actionText)->toBeNull()
        ->and($mail->subject)->toContain('Pat');
});

it('links the approvers\' email to roster.users.approvals.review_url with {user} replaced', function (): void {
    config()->set('roster.users.approvals.review_url', 'https://admin.test/users/{user}/review');
    $user = pendingUser();

    $mail = (new UserAwaitingApprovalNotification($user))->toMail(new AnonymousNotifiable);

    expect($mail->actionUrl)->toBe('https://admin.test/users/'.$user->getRouteKey().'/review')
        ->and($mail->actionText)->toBe(__('roster::roster.awaiting_approval_action'));
});

it('approves a pending user and tells them', function (): void {
    $user = app(ApproveUserAction::class)->execute(pendingUser(), actor: $this->approver);

    expect(app(Users::class)->status($user))->toBe(UserStatus::Active);
    Notification::assertSentOnDemand(UserApprovedNotification::class, fn ($n, $c, $notifiable): bool => $notifiable->routes['mail'] === 'pat@example.com');
});

it('rejects a pending user by deactivating them with the reason', function (): void {
    $user = app(RejectUserAction::class)->execute(pendingUser(), new RejectUserData(reason: 'Not a customer'), $this->approver);

    expect(app(Users::class)->status($user))->toBe(UserStatus::Deactivated)
        ->and($user->rosterProfile->status_reason)->toBe('Not a customer');
    Notification::assertSentOnDemand(UserRejectedNotification::class);
});

it('guards the approval transitions', function (): void {
    $active = user();

    expect(fn () => app(ApproveUserAction::class)->execute($active))->toThrow(ValidationException::class, 'not awaiting approval')
        ->and(fn () => app(RejectUserAction::class)->execute($active))->toThrow(ValidationException::class, 'not awaiting approval');

    $pending = pendingUser();

    expect(fn () => app(ReactivateUserAction::class)->execute($pending))->toThrow(ValidationException::class, 'approve it instead')
        ->and(fn () => app(ApproveUserAction::class)->execute($pending, actor: $pending))->toThrow(ValidationException::class);

    // A pending account can still be suspended.
    expect(app(Users::class)->status(app(SuspendUserAction::class)->execute($pending)))->toBe(UserStatus::Suspended);
});

it('sends no approval mail when turned off', function (): void {
    config()->set('roster.users.approvals.notify_approvers', false);
    config()->set('roster.users.approvals.notify_user', false);

    app(ApproveUserAction::class)->execute(pendingUser());

    Notification::assertNothingSent();
});

it('makes self-registered users pending only when configured', function (): void {
    $first = user(['email' => 'first@example.com']);
    event(new Registered($first));
    expect(app(Users::class)->status($first->fresh()))->toBe(UserStatus::Active);

    config()->set('roster.users.registration_status', 'pending');

    $second = user(['email' => 'second@example.com']);
    event(new Registered($second));
    expect(app(Users::class)->status($second->fresh()))->toBe(UserStatus::Pending);
    Notification::assertSentOnDemand(UserAwaitingApprovalNotification::class);

    // Never overrides a status someone already set.
    $third = user(['email' => 'third@example.com']);
    app(SuspendUserAction::class)->execute($third);
    event(new Registered($third));
    expect(app(Users::class)->status($third->fresh()))->toBe(UserStatus::Suspended);
});

it('blocks pending users with the approval message', function (): void {
    Route::middleware(['web', 'roster.active'])->get('/approval-check', fn (): string => 'ok');

    $this->actingAs(pendingUser())->get('/approval-check')->assertForbidden()->assertSee('awaiting approval');
});

it('lets organizations choose whether their externally provisioned accounts need approval', function (): void {
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);

    expect(app(Roster::class)->provisionedStatus($acme))->toBe(UserStatus::Active);

    app(UpdateOrganizationAction::class)->execute($acme, new UpdateOrganizationData(provisionedStatus: UserStatus::Pending));

    expect(app(Roster::class)->provisionedStatus($acme->refresh()))->toBe(UserStatus::Pending);
});

it('approves and rejects over http and mcp', function (): void {
    $this->actingAs($this->approver);

    $this->postJson(route('roster.users.store'), ['name' => 'Q', 'email' => 'q@example.com', 'status' => 'pending'])
        ->assertCreated()->assertJsonPath('data.status', 'pending');
    $q = User::query()->where('email', 'q@example.com')->sole();
    $this->postJson(route('roster.users.approve', $q->getRouteKey()))->assertOk()->assertJsonPath('data.status', 'active');
    $this->postJson(route('roster.users.approve', $q->getRouteKey()))->assertJsonValidationErrors('user');

    $r = pendingUser(['email' => 'r@example.com']);
    mcpTool(RejectUserTool::class, ['user' => $r->getRouteKey(), 'reason' => 'Spam'])->assertOk()->assertSee('deactivated');
});
