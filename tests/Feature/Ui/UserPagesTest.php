<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use RefactorCircus\Atrium\Domains\Screens\Data\ResolvedScreen;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Atrium\RosterPlugin;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use Workbench\App\Models\User;

/**
 * Every user screen test runs twice: with no user tab registered, and with
 * one visible to the viewer. Only the tab bar may differ between the two.
 *
 * @return array<string, array{Closure(): bool}>
 */
function userTabStates(): array
{
    return [
        'no tab registered' => [fn (): bool => false],
        'one visible tab registered' => [function (): bool {
            Atrium::screens()->tab('roster.user', TabDefinition::make('acme.orders')->label('Orders')->view('acme::orders')->public());

            return true;
        }],
    ];
}

/**
 * The user screen's tab bar: absent with no visible user tab, otherwise the
 * overview (open) and the registered tab.
 */
function userTabBar(TestResponse $response, bool $tabbed): TestResponse
{
    if (! $tabbed) {
        return $response->assertDontSee('data-testid="tab-', false);
    }

    return $response
        ->assertSeeInOrder(['data-testid="tab-overview"', 'data-testid="tab-acme.orders"'], false)
        ->assertViewHas('resolved', fn (ResolvedScreen $resolved): bool => $resolved->openTab === 'overview');
}

beforeEach(function (): void {
    $this->admin = user(['name' => 'Admin']);
    $this->actingAs($this->admin);
});

it('lists and filters users', function (bool $tabbed): void {
    $grace = user(['name' => 'Grace Hopper']);
    $grace->roster()->update(['status' => UserStatus::Suspended]);

    $this->get(route('atrium.roster.users.index'))->assertOk()->assertSee('Grace Hopper')->assertSee('Admin');

    $this->get(route('atrium.roster.users.index', ['status' => 'suspended']))
        ->assertOk()
        ->assertSee('Grace Hopper')
        ->assertDontSee('>Admin', false);
})->with(userTabStates());

it('creates a user from the dashboard', function (bool $tabbed): void {
    $this->get(route('atrium.roster.users.create'))->assertOk();

    $this->post(route('atrium.roster.users.store'), [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'password' => '',
        'display_name' => 'Countess',
    ])->assertRedirect();

    expect(User::query()->where('email', 'ada@example.com')->sole()->roster()->display_name)->toBe('Countess');
})->with(userTabStates());

it('edits a user and their profile', function (bool $tabbed): void {
    $user = user(['name' => 'Ada']);
    $password = $user->getAttribute('password');

    userTabBar($this->get(route('atrium.roster.users.show', $user->getRouteKey()))->assertOk()->assertSee('Ada'), $tabbed);

    $updated = $this->patch(route('atrium.roster.users.update', $user->getRouteKey()), [
        'name' => 'Ada King',
        'email' => $user->getAttribute('email'),
        'password' => '',
    ])->assertRedirect(route('atrium.roster.users.show', $user->getRouteKey()));

    // The overview is where an update lands, whatever tabs are registered.
    expect($updated->headers->get('Location'))->not->toContain('tab=');

    $this->patch(route('atrium.roster.users.profile', $user->getRouteKey()), ['bio' => 'Analyst'])->assertRedirect();

    $user->refresh();

    expect($user->getAttribute('name'))->toBe('Ada King')
        ->and($user->getAttribute('password'))->toBe($password)
        ->and($user->roster()->bio)->toBe('Analyst');
})->with(userTabStates());

it('changes status from the dashboard', function (bool $tabbed): void {
    $user = user();
    $key = $user->getRouteKey();

    $this->post(route('atrium.roster.users.suspend', $key), ['reason' => 'Spam'])->assertRedirect();
    expect($user->rosterStatus())->toBe(UserStatus::Suspended);

    userTabBar($this->get(route('atrium.roster.users.show', $key))->assertSee('Spam'), $tabbed);

    $this->post(route('atrium.roster.users.deactivate', $key))->assertRedirect();
    expect($user->refresh()->rosterStatus())->toBe(UserStatus::Deactivated);

    $this->post(route('atrium.roster.users.reactivate', $key))->assertRedirect();
    expect($user->refresh()->rosterStatus())->toBe(UserStatus::Active);
})->with(userTabStates());

it('shows domain guards as form errors', function (bool $tabbed): void {
    $this->from(route('atrium.roster.users.show', $this->admin->getRouteKey()))
        ->post(route('atrium.roster.users.suspend', $this->admin->getRouteKey()))
        ->assertSessionHasErrors('user');
})->with(userTabStates());

it('deletes a user from the dashboard only once confirmed', function (bool $tabbed): void {
    $user = user();

    userTabBar($this->get(route('atrium.roster.users.show', $user->getRouteKey()))->assertOk()->assertSee('data-testid="danger-zone"', false)->assertSee('Moves the user to Deleted'), $tabbed);

    $this->delete(route('atrium.roster.users.destroy', $user->getRouteKey()))->assertSessionHasErrors('confirm');
    expect(User::query()->whereKey($user->getKey())->exists())->toBeTrue();

    $this->delete(route('atrium.roster.users.destroy', $user->getRouteKey()), ['confirm' => '1'])
        ->assertRedirect(route('atrium.roster.users.index', ['trashed' => 'only']));

    expect(User::query()->whereKey($user->getKey())->exists())->toBeFalse();
})->with(userTabStates());

it('changes status from one form, offering only the moves that apply', function (bool $tabbed): void {
    $user = user();
    $key = $user->getRouteKey();

    userTabBar($this->get(route('atrium.roster.users.show', $key))
        ->assertOk()
        ->assertSee('Change status to')
        ->assertSee('<option value="suspended"', false)
        ->assertDontSee('<option value="active"', false), $tabbed);

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'suspended', 'reason' => 'Chargeback'])->assertRedirect();
    expect($user->fresh()->rosterStatus())->toBe(UserStatus::Suspended)
        ->and($user->fresh()->rosterProfile->status_reason)->toBe('Chargeback');

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'deactivated'])->assertRedirect();
    expect($user->fresh()->rosterStatus())->toBe(UserStatus::Deactivated);

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'active'])->assertRedirect();
    expect($user->fresh()->rosterStatus())->toBe(UserStatus::Active);

    $this->post(route('atrium.roster.users.status', $key), ['status' => 'pending'])->assertSessionHasErrors('status');
})->with(userTabStates());

it('renders the status widget', function (bool $tabbed): void {
    user()->roster()->update(['status' => UserStatus::Deactivated]);

    $widget = collect(app(RosterPlugin::class)->widgets())->firstOrFail(fn ($definition): bool => $definition->key === 'roster.user-status');

    $html = view('roster::ui.widgets.user-status', $widget->resolveData())->render();

    expect($html)->toContain('Deactivated');
})->with(userTabStates());
