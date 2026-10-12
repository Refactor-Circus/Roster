<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use Workbench\App\Models\User;

beforeEach(fn () => signInAsSuperAdmin());

it('lists and searches users', function (): void {
    user(['name' => 'Grace Hopper']);
    user(['name' => 'Alan Turing']);

    visit('/atrium/roster/users')
        ->assertSee('Grace Hopper')
        ->assertSee('Alan Turing')
        ->type('search', 'grace')
        ->click('@filter-users')
        ->assertSee('Grace Hopper')
        ->assertDontSee('Alan Turing');
});

it('creates a user and suspends them', function (): void {
    visit('/atrium/roster/users')
        ->click('@new-user')
        ->type('name', 'Ada Lovelace')
        ->type('email', 'ada@example.com')
        ->click('@create-user')
        ->assertSee('User created.')
        ->select('#new-status', 'suspended')
        ->type('#status-reason', 'Testing')
        ->click('@change-status')
        ->assertSee('User suspended.')
        ->assertSee('Testing');

    expect(User::query()->where('email', 'ada@example.com')->sole()->rosterStatus())->toBe(UserStatus::Suspended);
});

it('approves a user who is waiting for approval', function (): void {
    $pending = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Pat Pending', email: 'pat@example.com', status: UserStatus::Pending));

    visit('/atrium/roster/users?status=pending')->assertSee('Pat Pending');

    visit('/atrium/roster/users/'.$pending->getRouteKey())
        ->assertPresent('@approval-card')
        ->click('@approve-user')
        ->assertSee('Account approved.')
        ->assertMissing('@approval-card');

    expect($pending->fresh()->rosterStatus()->value)->toBe('active');
});

it('deletes a user from the danger zone after confirming', function (): void {
    $doomed = user(['name' => 'Doomed User', 'email' => 'doomed@example.com']);

    visit('/atrium/roster/users/'.$doomed->getRouteKey())
        ->assertSee('Danger zone')
        ->check('#confirm-delete')
        ->click('@delete-user')
        ->assertSee('User deleted.');

    expect(User::query()->whereKey($doomed->getKey())->exists())->toBeFalse();
});

it('restores a deleted user from the deleted list', function (): void {
    $pat = user(['name' => 'Pat Restore', 'email' => 'pat.restore@example.com']);

    visit('/atrium/roster/users/'.$pat->getRouteKey())
        ->check('#confirm-delete')
        ->click('@delete-user')
        ->assertSee('User deleted.')
        ->assertSee('Pat Restore')
        ->click('@restore-user')
        ->assertSee('User restored.');

    expect($pat->fresh()->trashed())->toBeFalse();
});
