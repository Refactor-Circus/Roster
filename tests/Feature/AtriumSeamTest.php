<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\AtriumServiceProvider;
use RefactorCircus\Roster\Atrium\InvitationPage as AtriumInvitationPage;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Invitation\Services\PlainInvitationPage;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\RosterServiceProvider;

/**
 * With Atrium installed, Roster's Atrium seam points the invitation page and
 * the impersonation banner at Atrium's views, declares `roster.invitation`
 * and registers `@rosterCan`.
 */
function invitationPageUrl(): string
{
    Notification::fake();

    $organization = organization(attributes: ['name' => 'Acme']);
    app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));
    app(CreateInvitationAction::class)->execute($organization, new CreateInvitationData(email: 'ada@example.com', teams: ['ops']));

    $url = '';
    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification) use (&$url): bool {
        $url = $notification->url();

        return true;
    });

    return $url;
}

it('registers the seam while Atrium is installed', function (): void {
    expect($this->app->getProvider(AtriumServiceProvider::class))->not->toBeNull()
        ->and(app(InvitationPage::class))->toBeInstanceOf(AtriumInvitationPage::class);
});

it('renders the invitation page with Atrium', function (): void {
    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get(invitationPageUrl())
        ->assertOk()
        ->assertSee('data-testid="guest-layout"', false)
        ->assertSee('Join Acme')
        ->assertSee('Ops')
        ->assertSee('data-testid="accept-invitation"', false)
        ->assertSee('data-testid="decline-invitation"', false)
        ->assertDontSee('data-testid="invitation-plain"', false);
});

it('declares the invitation page as roster.invitation', function (): void {
    $directory = sys_get_temp_dir().'/roster-atrium-seam-'.getmypid();

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory.'/section.blade.php', '<span>{{ $screen->key }} {{ $record?->email }}</span>');
    View::addNamespace('atriumseam', $directory);

    Atrium::screens()->section('roster.invitation', 'aside', SectionDefinition::make('test.aside')->view('atriumseam::section')->public());

    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get(invitationPageUrl())
        ->assertOk()
        ->assertSee('data-testid="section-test.aside"', false)
        ->assertSee('roster.invitation ada@example.com');
});

it('renders the impersonation banner with Atrium', function (): void {
    Route::middleware('web')->get('roster-test/banner', fn (): string => Blade::render("@include('roster::impersonation-banner')"));
    app('router')->getRoutes()->refreshNameLookups();

    $admin = user(['name' => 'Admin']);
    $ada = user(['name' => 'Ada']);
    $this->actingAs($admin);
    $started = app(StartImpersonationAction::class)->execute($ada, new StartImpersonationData(reason: 'Ticket 42'), $admin);

    $this->get($started->url)->assertRedirect('/');

    $this->get('roster-test/banner')
        ->assertOk()
        ->assertSee('data-testid="impersonation-banner"', false)
        ->assertSee('data-testid="leave-impersonation"', false)
        ->assertSee('You are acting as Ada (signed in as Admin).')
        // Atrium's standalone banner, styled with its theme tokens.
        ->assertSee('var(--color-warning', false);
});

it('registers @rosterCan', function (): void {
    expect(Blade::getCustomDirectives())->toHaveKey('rosterCan');
});

it('binds the plain invitation page when Roster skips its Atrium seam', function (): void {
    RosterServiceProvider::$withoutAtrium = true;

    try {
        $this->refreshApplication();

        expect($this->app->getProvider(AtriumServiceProvider::class))->toBeNull()
            ->and(app(InvitationPage::class))->toBeInstanceOf(PlainInvitationPage::class)
            ->and(Blade::getCustomDirectives())->not->toHaveKey('rosterCan');
    } finally {
        RosterServiceProvider::$withoutAtrium = false;
    }
});
