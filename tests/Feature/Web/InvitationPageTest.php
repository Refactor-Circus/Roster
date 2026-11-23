<?php

declare(strict_types=1);

use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Enums\InvitationStatus;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Invitation\Services\PlainInvitationPage;
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
    expect(app(InvitationPage::class))->toBeInstanceOf(PlainInvitationPage::class);

    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get($this->url)
        ->assertOk()
        ->assertSee('data-testid="invitation-plain"', false)
        ->assertSee('Join Acme')
        ->assertSee('Ops')
        ->assertSee('data-testid="accept-invitation"', false)
        ->assertSee('data-testid="decline-invitation"', false);
});

it('renders through a host\'s own InvitationPage binding', function (): void {
    $this->app->singleton(InvitationPage::class, fn (): InvitationPage => new class implements InvitationPage
    {
        public function render(InvitationModel $invitation, string $token, Request $request): ViewContract
        {
            /** @var view-string $view */
            $view = 'host-invitation::page';

            return view($view, PlainInvitationPage::data($invitation, $token));
        }
    });

    $directory = sys_get_temp_dir().'/roster-host-invitation-'.getmypid();

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory.'/page.blade.php', '<p data-testid="host-invitation">{{ $organization->name }} {{ $invitation->email }} {{ $teams->implode(\',\') }}</p>');
    View::addNamespace('host-invitation', $directory);

    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get($this->url)
        ->assertOk()
        ->assertSee('data-testid="host-invitation"', false)
        ->assertSee('Acme ada@example.com Ops')
        ->assertDontSee('data-testid="invitation-plain"', false);
});

it('links the email to roster.invitations.accept_url instead of the page when it is set', function (): void {
    config()->set('roster.invitations.accept_url', 'https://app.test/join/{token}');

    $notification = new InvitationNotification(InvitationModel::query()->sole(), $this->token);

    expect($notification->toMail(new AnonymousNotifiable)->actionUrl)->toBe('https://app.test/join/'.$this->token)
        // Without it, the email links to the signed page.
        ->and($this->url)->toContain('signature=');
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
