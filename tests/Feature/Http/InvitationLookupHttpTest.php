<?php

declare(strict_types=1);

use Illuminate\Routing\RouteCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationLookedUpActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationLookingUpActionEvent;
use RefactorCircus\Roster\Domains\Invitation\InvitationServiceProvider;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

/**
 * Register the Invitation domain's routes again, from the current config, on
 * an empty route collection.
 */
function reloadInvitationRoutes(): void
{
    app('router')->setRoutes(new RouteCollection);
    app()->register(InvitationServiceProvider::class, force: true);
    app('router')->getRoutes()->refreshNameLookups();
}

function lookUpInvitation(string $token, string $ip = '127.0.0.1'): TestResponse
{
    return test()->withServerVariables(['REMOTE_ADDR' => $ip])->getJson(route('roster.invitations.lookup', $token));
}

it('shows a guest holding the token what the invitation is for', function (): void {
    config()->set('roster.authorization', true);
    $organization = organization(attributes: ['name' => 'Acme']);
    $zeta = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Zeta'));
    $alpha = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Alpha'));
    $expires = now()->addDays(3)->startOfSecond();
    $token = invitationToken($organization, [$zeta, $alpha], ['email' => 'ada@example.com', 'expires_at' => $expires]);

    $response = lookUpInvitation($token)
        ->assertOk()
        ->assertExactJson(['data' => [
            'email' => 'ada@example.com',
            'organization' => ['name' => 'Acme'],
            'teams' => ['Alpha', 'Zeta'],
            'expires_at' => $expires->toIso8601String(),
        ]]);

    expect($this->isAuthenticated())->toBeFalse()
        ->and($response->baseResponse->headers->hasCacheControlDirective('no-store'))->toBeTrue();
});

it('answers every unusable token with the same 404', function (string $state): void {
    $organization = organization();
    $token = invitationToken($organization);
    $invitation = InvitationModel::query()->sole();

    match ($state) {
        'unknown' => $token = InvitationTokens::generate(),
        'expired' => $invitation->update(['expires_at' => now()->subMinute()]),
        'revoked' => $invitation->update(['revoked_at' => now()]),
        'accepted' => $invitation->update(['accepted_at' => now()]),
        'declined' => $invitation->update(['declined_at' => now()]),
        'deleted organization' => $organization->delete(),
    };

    $unknown = lookUpInvitation(InvitationTokens::generate())->assertNotFound();
    $response = lookUpInvitation($token)->assertNotFound()->assertExactJson(['message' => 'Not found.']);

    expect($response->getContent())->toBe($unknown->getContent())
        ->and($response->baseResponse->headers->hasCacheControlDirective('no-store'))->toBeTrue();
})->with(['unknown', 'expired', 'revoked', 'accepted', 'declined', 'deleted organization']);

it('answers a malformed token 404 before any query runs', function (string $token): void {
    DB::enableQueryLog();

    $this->getJson(route('roster.invitations.lookup', $token))->assertNotFound();

    expect(DB::getQueryLog())->toBe([]);
})->with([
    'too short' => [str_repeat('a', 47)],
    'too long' => [str_repeat('a', 49)],
    'not alphanumeric' => [str_repeat('a', 47).'-'],
    'underscore' => [str_repeat('a', 47).'_'],
]);

it('never puts the token, its hash, ids, slugs, the inviter or the status in the payload', function (): void {
    $inviter = user();
    $organization = organization(attributes: ['name' => 'Acme Corp']);
    $token = invitationToken($organization, attributes: ['email' => 'ada@example.com', 'invited_by' => $inviter->getKey()]);
    $invitation = InvitationModel::query()->sole();

    $response = lookUpInvitation($token)->assertOk();

    foreach (['id', 'token', 'token_hash', 'slug', 'inviter', 'invited_by', 'status', 'created_at', 'updated_at', 'accepted_at', 'declined_at', 'revoked_at', 'organization.id', 'organization.slug'] as $path) {
        $response->assertJsonMissingPath('data.'.$path);
    }

    expect($response->getContent())
        ->not->toContain($token)
        ->not->toContain($invitation->token_hash)
        ->not->toContain((string) $invitation->getKey())
        ->not->toContain($organization->slug);
});

it('keeps the plain token out of both events', function (): void {
    Event::fake([InvitationLookingUpActionEvent::class, InvitationLookedUpActionEvent::class]);
    $token = invitationToken(organization());

    lookUpInvitation($token)->assertOk();

    Event::assertDispatched(InvitationLookingUpActionEvent::class, fn (InvitationLookingUpActionEvent $event): bool => ! str_contains(serialize($event), $token));
    Event::assertDispatched(InvitationLookedUpActionEvent::class, fn (InvitationLookedUpActionEvent $event): bool => ! str_contains(serialize($event), $token));
});

it('stays public while the API middleware authenticates', function (): void {
    config()->set('roster.routes.middleware', ['api', 'auth']);
    reloadInvitationRoutes();
    $token = invitationToken(organization());

    expect(Route::getRoutes()->getByName('roster.invitations.accept')?->gatherMiddleware())->toContain('auth')
        ->and(Route::getRoutes()->getByName('roster.invitations.lookup')?->gatherMiddleware())->toBe(['api', 'throttle:roster-invitation-lookup']);

    $this->postJson(route('roster.invitations.accept', $token))->assertUnauthorized();
    lookUpInvitation($token)->assertOk();
});

it('uses the lookup middleware and the API prefix from config', function (): void {
    config()->set('roster.routes.prefix', 'people');
    config()->set('roster.invitations.lookup_middleware', ['api']);
    reloadInvitationRoutes();
    $token = invitationToken(organization());

    expect(route('roster.invitations.lookup', $token, false))->toBe('/people/invitations/'.$token)
        ->and(Route::getRoutes()->getByName('roster.invitations.lookup')?->gatherMiddleware())->toBe(['api']);
});

it('is absent while the API routes are disabled', function (): void {
    config()->set('roster.routes.enabled', false);
    reloadInvitationRoutes();

    expect(Route::has('roster.invitations.lookup'))->toBeFalse()
        ->and(Route::has('roster.invitations.accept'))->toBeFalse()
        // The emailed page is always on.
        ->and(Route::has('roster.invitations.show'))->toBeTrue();
});

it('allows ten lookups per IP a minute, whatever the tokens', function (bool $validFirst): void {
    $valid = invitationToken(organization());
    $invalid = InvitationTokens::generate();

    foreach (range(1, 10) as $ignored) {
        lookUpInvitation($validFirst ? $valid : $invalid)->assertStatus($validFirst ? 200 : 404);
    }

    lookUpInvitation($validFirst ? $invalid : $valid)->assertTooManyRequests();
    lookUpInvitation($valid)->assertTooManyRequests();

    // Another address has its own allowance.
    lookUpInvitation($valid, '10.0.0.2')->assertOk();
})->with([
    'valid tokens first' => [true],
    'invalid tokens first' => [false],
]);

it('follows the configured lookup limit, and can turn it off', function (): void {
    $token = invitationToken(organization());

    config()->set('roster.rate_limit.invitation_lookups_per_minute', 2);
    lookUpInvitation($token)->assertOk();
    lookUpInvitation($token)->assertOk();
    lookUpInvitation($token)->assertTooManyRequests();

    config()->set('roster.rate_limit.invitation_lookups_per_minute', null);

    foreach (range(1, 12) as $ignored) {
        lookUpInvitation($token, '10.0.0.3')->assertOk();
    }
});

it('limits lookups apart from the rest of the API', function (): void {
    config()->set('roster.rate_limit.per_minute', 1);
    $token = invitationToken(organization());

    lookUpInvitation($token)->assertOk();
    lookUpInvitation($token)->assertOk();
});
