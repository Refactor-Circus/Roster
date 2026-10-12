<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

it('lists users', function (): void {
    user(['name' => 'Ada']);

    $this->getJson(route('roster.users.index'))
        ->assertOk()
        ->assertJsonPath('data.0.name', 'Ada')
        ->assertJsonPath('data.0.status', 'active')
        ->assertJsonPath('meta.total', 1);
});

it('rejects an unknown status filter', function (): void {
    $this->getJson(route('roster.users.index', ['status' => 'banned']))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('creates a user', function (): void {
    $this->postJson(route('roster.users.store'), [
        'name' => 'Ada',
        'email' => 'ada@example.com',
        'display_name' => 'Countess',
    ])
        ->assertCreated()
        ->assertJsonPath('data.email', 'ada@example.com')
        ->assertJsonPath('data.profile.display_name', 'Countess')
        ->assertJsonMissingPath('data.password');
});

it('shows a user by route key', function (): void {
    $user = user();

    $this->getJson(route('roster.users.show', $user->getRouteKey()))
        ->assertOk()
        ->assertJsonPath('data.id', $user->getRouteKey());
});

it('returns 404 for an unknown user', function (): void {
    $this->getJson(route('roster.users.show', 999))->assertNotFound();
});

it('updates a user and their profile', function (): void {
    $user = user();

    $this->patchJson(route('roster.users.update', $user->getRouteKey()), ['name' => 'Renamed'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Renamed');

    $this->patchJson(route('roster.users.profile.update', $user->getRouteKey()), ['locale' => 'fr'])
        ->assertOk()
        ->assertJsonPath('data.profile.locale', 'fr');
});

it('moves a user through every status', function (): void {
    $user = user();

    $this->postJson(route('roster.users.suspend', $user->getRouteKey()), ['reason' => 'Abuse'])
        ->assertOk()
        ->assertJsonPath('data.status', 'suspended')
        ->assertJsonPath('data.status_reason', 'Abuse');

    $this->postJson(route('roster.users.deactivate', $user->getRouteKey()))
        ->assertOk()
        ->assertJsonPath('data.status', 'deactivated');

    $this->postJson(route('roster.users.reactivate', $user->getRouteKey()))
        ->assertOk()
        ->assertJsonPath('data.status', 'active');
});

it('maps a domain guard to a 422', function (): void {
    $user = user();

    $this->postJson(route('roster.users.reactivate', $user->getRouteKey()))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');
});

it('stops an authenticated user suspending themselves', function (): void {
    $user = user();

    $this->actingAs($user)
        ->postJson(route('roster.users.suspend', $user->getRouteKey()))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user');
});

it('deletes a user', function (): void {
    $user = user();
    $user->roster();

    $this->deleteJson(route('roster.users.destroy', $user->getRouteKey()))->assertNoContent();
    $this->getJson(route('roster.users.show', $user->getRouteKey()))->assertNotFound();

    $this->postJson(route('roster.users.restore', $user->getRouteKey()))->assertOk();
    $this->deleteJson(route('roster.users.destroy', $user->getRouteKey()))->assertNoContent();
    $this->deleteJson(route('roster.users.purge', $user->getRouteKey()))->assertNoContent();

    expect(ProfileModel::query()->count())->toBe(0);
});

it('validates input once, through the request, with the Data rules', function (): void {
    user(['email' => 'taken@example.com']);

    $this->postJson(route('roster.users.store'), ['email' => 'taken@example.com', 'password' => 'short', 'status' => 'nope'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'email', 'password', 'status']);
});
