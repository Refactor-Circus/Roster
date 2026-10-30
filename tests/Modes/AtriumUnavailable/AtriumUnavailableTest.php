<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification;
use RefactorCircus\Roster\Domains\Invitation\Services\PlainInvitationPage;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ListUsersTool;

/**
 * Roster without Atrium: its API, MCP server, invitation page and
 * impersonation banner still work; the dashboard screens are simply absent.
 */
it('boots', function (): void {
    expect($this->app->isBooted())->toBeTrue()
        ->and($this->app->getProvider('RefactorCircus\Roster\Atrium\AtriumServiceProvider'))->toBeNull();
});

it('registers no dashboard routes', function (): void {
    expect(Route::has('atrium.roster.users.index'))->toBeFalse();
});

it('serves the HTTP API', function (): void {
    $this->postJson(route('roster.organizations.store'), ['name' => 'Acme'])->assertCreated();

    $this->getJson(route('roster.users.index'))->assertOk();
});

it('serves the MCP tools', function (): void {
    user(['name' => 'Ada']);

    mcpTool(ListUsersTool::class)->assertOk();
});

it('registers no Atrium-only Blade directive', function (): void {
    expect(Blade::getCustomDirectives())->not->toHaveKey('rosterCan');
});

it('shows the plain invitation page', function (): void {
    Notification::fake();

    $organization = organization(attributes: ['name' => 'Acme']);
    app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));
    app(CreateInvitationAction::class)->execute($organization, new CreateInvitationData(email: 'ada@example.com', teams: ['ops']));

    $url = null;
    Notification::assertSentOnDemand(InvitationNotification::class, function (InvitationNotification $notification) use (&$url): bool {
        $url = $notification->url();

        return true;
    });

    expect(app(InvitationPage::class))->toBeInstanceOf(PlainInvitationPage::class);

    $this->actingAs(user(['email' => 'ada@example.com']))
        ->get($url)
        ->assertOk()
        ->assertSee('data-testid="invitation-plain"', false)
        ->assertSee('Join Acme')
        ->assertSee('Ops')
        ->assertSee('data-testid="accept-invitation"', false)
        ->assertSee('data-testid="decline-invitation"', false)
        ->assertDontSee('data-testid="guest-layout"', false)
        ->assertDontSee('vendor/atrium/atrium.css', false);
});

it('shows the plain impersonation banner', function (): void {
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
        ->assertSee('Return to my account')
        // Atrium's standalone banner styles itself with its theme tokens.
        ->assertDontSee('var(--color-warning', false);
});
