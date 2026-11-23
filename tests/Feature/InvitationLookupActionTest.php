<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Invitation\Actions\LookUpInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\LookUpInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationLookedUpActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Events\InvitationLookingUpActionEvent;
use RefactorCircus\Roster\Domains\Invitation\Exceptions\InvalidInvitationException;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Invitation\Services\InvitationTokens;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

it('finds a pending invitation by its token, with its organization and teams loaded', function (): void {
    Event::fake([InvitationLookingUpActionEvent::class, InvitationLookedUpActionEvent::class]);
    $organization = organization(attributes: ['name' => 'Acme']);
    $ops = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));
    $token = invitationToken($organization, [$ops], ['email' => 'ada@example.com']);

    $invitation = app(LookUpInvitationAction::class)->execute(new LookUpInvitationData(token: $token));

    expect($invitation->is(InvitationModel::query()->sole()))->toBeTrue()
        ->and($invitation->relationLoaded('organization'))->toBeTrue()
        ->and($invitation->organization?->is($organization))->toBeTrue()
        ->and($invitation->relationLoaded('teamModels'))->toBeTrue()
        ->and($invitation->getRelation('teamModels')->pluck('name')->all())->toBe(['Ops']);

    Event::assertDispatchedTimes(InvitationLookingUpActionEvent::class, 1);
    Event::assertDispatched(InvitationLookedUpActionEvent::class, fn (InvitationLookedUpActionEvent $event): bool => $event->invitation->is($invitation));
});

it('refuses every token that is not a pending invitation in a live organization', function (string $state): void {
    Event::fake([InvitationLookingUpActionEvent::class, InvitationLookedUpActionEvent::class]);
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

    try {
        app(LookUpInvitationAction::class)->execute(new LookUpInvitationData(token: $token));
        $this->fail('The lookup should have been refused.');
    } catch (InvalidInvitationException $exception) {
        // One message for every refusal, so none tells which it was.
        expect($exception->getMessage())->toBe(InvalidInvitationException::forToken()->getMessage());
    }

    // A refused lookup starts and never finishes.
    Event::assertDispatched(InvitationLookingUpActionEvent::class);
    Event::assertNotDispatched(InvitationLookedUpActionEvent::class);
})->with(['unknown', 'expired', 'revoked', 'accepted', 'declined', 'deleted organization']);

it('matches the hash of the token, never the stored value itself', function (): void {
    invitationToken(organization());
    $stored = InvitationModel::query()->sole()->token_hash;

    app(LookUpInvitationAction::class)->execute(new LookUpInvitationData(token: $stored));
})->throws(InvalidInvitationException::class);

it('carries neither the input nor the token on its events', function (): void {
    Event::fake([InvitationLookingUpActionEvent::class, InvitationLookedUpActionEvent::class]);
    $token = invitationToken(organization());

    app(LookUpInvitationAction::class)->execute(new LookUpInvitationData(token: $token));

    $starting = Event::dispatched(InvitationLookingUpActionEvent::class)->sole()[0];
    $finished = Event::dispatched(InvitationLookedUpActionEvent::class)->sole()[0];

    expect(array_keys(get_object_vars($starting)))->toBe([])
        ->and(array_keys(get_object_vars($finished)))->toBe(['invitation'])
        ->and(serialize($starting))->not->toContain($token)
        ->and(serialize($finished))->not->toContain($token);
});

it('validates the token shape', function (mixed $token): void {
    LookUpInvitationData::from(['token' => $token]);
})->with([
    'missing' => [null],
    'too short' => [str_repeat('a', 47)],
    'too long' => [str_repeat('a', 49)],
    'not alphanumeric' => [str_repeat('a', 47).'-'],
])->throws(ValidationException::class);
