<?php

declare(strict_types=1);

use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
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

it('lets organizations choose whether their SCIM accounts need approval', function (): void {
    require_once __DIR__.'/Scim/helpers.php';
    [$acme, $token] = scimOrg();
    test()->scimToken = $token;

    scim('POST', '/Users', oktaUser('one@acme.test', 'e1'))->assertCreated();
    app(UpdateOrganizationAction::class)->execute($acme, new UpdateOrganizationData(provisionedStatus: UserStatus::Pending));
    scim('POST', '/Users', oktaUser('two@acme.test', 'e2'))->assertCreated();

    expect(app(Users::class)->status(User::query()->where('email', 'one@acme.test')->sole()))->toBe(UserStatus::Active)
        ->and(app(Users::class)->status(User::query()->where('email', 'two@acme.test')->sole()))->toBe(UserStatus::Pending);
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

it('approves from the user page in atrium', function (): void {
    $this->actingAs($this->approver);
    $pending = pendingUser();

    $this->get(route('atrium.roster.users.show', $pending->getRouteKey()))->assertOk()->assertSee('data-testid="approval-card"', false);
    $this->post(route('atrium.roster.users.approve', $pending->getRouteKey()))->assertRedirect();
    $this->get(route('atrium.roster.users.show', $pending->getRouteKey()))->assertDontSee('data-testid="approval-card"', false);

    expect(app(Users::class)->status($pending->fresh()))->toBe(UserStatus::Active);
});

it('activates pending users straight from the users list and an organization\'s members', function (): void {
    $this->actingAs($this->approver);
    $acme = organization(attributes: ['name' => 'Acme']);
    $listed = pendingUser(['email' => 'listed@example.com']);
    $member = pendingUser(['email' => 'member@example.com']);
    MembershipModel::query()->create(['organization_id' => $acme->id, 'user_id' => $member->getKey()]);

    $this->get(route('atrium.roster.users.index'))->assertSee('data-testid="activate-user"', false);
    $this->from(route('atrium.roster.users.index'))
        ->post(route('atrium.roster.users.approve', $listed->getRouteKey()))
        ->assertRedirect(route('atrium.roster.users.index'));

    $members = route('atrium.roster.organizations.show', 'acme');
    $this->get($members)->assertSee('data-testid="activate-user"', false);
    $this->from($members)->post(route('atrium.roster.users.approve', $member->getRouteKey()))->assertRedirect($members);
    $this->get($members)->assertDontSee('data-testid="activate-user"', false);

    expect(app(Users::class)->status($listed->fresh()))->toBe(UserStatus::Active)
        ->and(app(Users::class)->status($member->fresh()))->toBe(UserStatus::Active);
});
