<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

beforeEach(function (): void {
    // Impersonating compares what both users may do, which only a role
    // store can list: without one only a super-admin may impersonate.
    withRoleStore();

    Route::middleware('web')->get('roster-test/whoami', fn (): string => (string) Auth::id().'|'.view('roster::impersonation-banner')->render());
    Route::middleware('web')->get('roster-test/banner', fn () => Blade::render("@include('roster::impersonation-banner')"));
    app('router')->getRoutes()->refreshNameLookups();

    $this->admin = user(['name' => 'Admin']);
    $this->ada = user(['name' => 'Ada']);
    $this->actingAs($this->admin);
    $this->started = app(StartImpersonationAction::class)->execute($this->ada, new StartImpersonationData(reason: 'Ticket 42'), $this->admin);
});

it('becomes the user, shows the banner, and returns', function (): void {
    $this->get($this->started->url)->assertRedirect('/');

    expect(Auth::id())->toBe($this->ada->getKey());

    // The plain banner, styled inline (roster::partials.impersonation-banner-plain).
    $this->get('roster-test/banner')
        ->assertOk()
        ->assertSee('data-testid="impersonation-banner"', false)
        ->assertSee('data-testid="leave-impersonation"', false)
        ->assertSee('background:#b45309', false)
        ->assertSee('You are acting as Ada (signed in as Admin).')
        ->assertSee('Return to my account');

    // No roster.impersonation.return_to here: back to the home page.
    $this->post(route('roster.impersonation.leave'))->assertRedirect('/');

    expect(Auth::id())->toBe($this->admin->getKey())
        ->and($this->started->impersonation->refresh()->end_reason)->toBe('stopped');

    $this->get('roster-test/banner')->assertDontSee('You are acting as');
});

it('renders the plain banner partial and nothing else', function (): void {
    $this->get($this->started->url);

    $rendered = [];
    View::composer('*', function (Illuminate\View\View $view) use (&$rendered): void {
        $rendered[] = $view->name();
    });

    $this->get('roster-test/whoami')->assertOk();

    expect($rendered)->toBe(['roster::impersonation-banner', 'roster::partials.impersonation-banner-plain']);
});

it('shares no rosterUsesAtrium flag with the views', function (): void {
    expect(View::getShared())->not->toHaveKey('rosterUsesAtrium');
});

it('returns to roster.impersonation.return_to when it is set', function (): void {
    config()->set('roster.impersonation.return_to', '/admin/users');

    $this->get($this->started->url);

    $this->post(route('roster.impersonation.leave'))->assertRedirect('/admin/users');

    expect(Auth::id())->toBe($this->admin->getKey());
});

it('returns to / when roster.impersonation.return_to is empty', function (): void {
    config()->set('roster.impersonation.return_to', '');

    $this->get($this->started->url);

    $this->post(route('roster.impersonation.leave'))->assertRedirect('/');
});

it('refuses the link in someone else\'s browser', function (): void {
    $this->actingAs(user())->get($this->started->url)->assertSessionHasErrors('token');
});

it('needs a valid signature', function (): void {
    $this->get(route('roster.impersonation.enter', 'tampered'))->assertForbidden();
});

it('returns the impersonator when time runs out', function (): void {
    $this->get($this->started->url);

    $this->travel(31)->minutes();

    $this->get('roster-test/banner')->assertSessionHas('status');

    expect(Auth::id())->toBe($this->admin->getKey())
        ->and($this->started->impersonation->refresh()->end_reason)->toBe('expired');
});

it('returns the impersonator when ended elsewhere', function (): void {
    $this->get($this->started->url);

    app(StopImpersonationAction::class)->execute($this->started->impersonation->refresh(), new StopImpersonationData(why: ImpersonationModel::ENDED_FORCED));

    $this->get('roster-test/banner');

    expect(Auth::id())->toBe($this->admin->getKey());
});

it('stays impersonating across normal requests', function (): void {
    $this->get($this->started->url);
    $this->get('roster-test/banner');
    $this->get('roster-test/banner');

    expect(Auth::id())->toBe($this->ada->getKey());
});
