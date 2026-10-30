<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

beforeEach(function (): void {
    Notification::fake();

    $this->organization = organization(attributes: ['name' => 'Acme']);
    app(CreateTeamAction::class)->execute($this->organization, new CreateTeamData(name: 'Ops'));
    app(CreateInvitationAction::class)->execute($this->organization, new CreateInvitationData(email: 'ada@example.com', teams: ['ops']));

    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification): bool {
        $this->token = $notification->token;
        $this->url = $notification->url();

        return true;
    });
});

it('shows the invitation to the signed-in invitee', function (): void {
    // Atrium is installed here, so the page is Atrium's guest page
    // (roster::ui.invitations.show); tests/Modes/AtriumUnavailable covers the plain one.
    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get($this->url)
        ->assertOk()
        ->assertSee('data-testid="guest-layout"', false)
        ->assertSee('Join Acme')
        ->assertSee('Ops');
});

it('requires a valid signature', function (): void {
    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get(route('roster.invitations.show', $this->token))
        ->assertForbidden();
});

it('sends guests to log in first', function (): void {
    Route::get('login', fn (): string => 'login')->name('login');
    app('router')->getRoutes()->refreshNameLookups();

    $this->get($this->url)->assertRedirect(route('login'));
});

it('accepts and redirects', function (): void {
    config()->set('roster.invitations.redirect', '/dashboard');
    $ada = user(['email' => 'ada@example.com']);

    $this->actingAs($ada)
        ->post(route('roster.invitations.page.accept', $this->token))
        ->assertRedirect('/dashboard');

    expect($this->organization->membershipFor($ada))->not->toBeNull()
        ->and($this->organization->teams()->sole()->hasMember($ada))->toBeTrue();
});

it('declines', function (): void {
    $this->actingAs(user(['email' => 'ada@example.com']))
        ->post(route('roster.invitations.page.decline', $this->token))
        ->assertRedirect('/');

    expect(InvitationModel::query()->sole()->status())->toBe(InvitationStatus::Declined);
});

it('shows an error to the wrong user', function (): void {
    $this->actingAs(user(['email' => 'eve@example.com']))
        ->from($this->url)
        ->post(route('roster.invitations.page.accept', $this->token))
        ->assertSessionHasErrors('token');
});

it('returns 404 for an unknown token', function (): void {
    $this->actingAs(user())
        ->get(URL::signedRoute('roster.invitations.show', 'nope'))
        ->assertNotFound();
});
