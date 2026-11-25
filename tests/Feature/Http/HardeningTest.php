<?php

declare(strict_types=1);

use Illuminate\Support\Facades\RateLimiter;

it('throttles the API per user', function (): void {
    config()->set('roster.rate_limit.per_minute', 2);
    RateLimiter::clear('roster');
    $this->actingAs(user());

    $this->getJson(route('roster.users.index'))->assertOk();
    $this->getJson(route('roster.users.index'))->assertOk();
    $this->getJson(route('roster.users.index'))->assertTooManyRequests();

    // Another user has their own allowance.
    $this->actingAs(user())->getJson(route('roster.users.index'))->assertOk();
});

it('can turn the limiter off', function (): void {
    config()->set('roster.rate_limit.per_minute', null);

    foreach (range(1, 5) as $ignored) {
        $this->getJson(route('roster.users.index'))->assertOk();
    }
});

it('answers missing records without naming model classes', function (string $name, array $parameters, string $method = 'GET'): void {
    $this->json($method, route($name, $parameters))
        ->assertNotFound()
        ->assertExactJson(['message' => 'Not found.']);
})->with([
    'user' => ['roster.users.show', [999]],
    'organization' => ['roster.organizations.show', ['missing']],
    'team' => ['roster.organizations.teams.show', ['missing', 'ops']],
    'transfer' => ['roster.transfers.show', ['01JAAAAAAAAAAAAAAAAAAAAAAA']],
    'impersonation' => ['roster.impersonations.destroy', ['01JAAAAAAAAAAAAAAAAAAAAAAA'], 'DELETE'],
]);

it('leaves 404s outside roster alone', function (): void {
    $this->getJson('/definitely-not-a-route')->assertNotFound()->assertJsonMissing(['message' => 'Not found.']);
});

it('returns field-keyed 422s on every surface', function (): void {
    $this->postJson(route('roster.users.store'), [])
        ->assertUnprocessable()
        ->assertJsonStructure(['message', 'errors' => ['email']]);

    $this->postJson(route('roster.organizations.store'), ['name' => 'X', 'owner' => 999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('owner');
});
