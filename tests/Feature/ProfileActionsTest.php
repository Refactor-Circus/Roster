<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\User\Actions\DeactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\ReactivateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\DeactivateUserData;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;

it('creates a profile lazily on first update', function (): void {
    $user = user();

    expect(ProfileModel::query()->count())->toBe(0)
        ->and($user->rosterStatus())->toBe(UserStatus::Active);

    $updated = app(UpdateProfileAction::class)->execute($user, new UpdateProfileData(bio: 'Mathematician', meta: ['team' => 'x']));

    expect(ProfileModel::query()->count())->toBe(1)
        ->and($updated->rosterProfile?->bio)->toBe('Mathematician')
        ->and($updated->rosterProfile?->meta)->toBe(['team' => 'x']);
});

it('validates profile fields', function (array $data): void {
    UpdateProfileData::from($data);
})->with([
    'bad timezone' => [['timezone' => 'Mars/Olympus']],
    'bad avatar url' => [['avatar_url' => 'not a url']],
    'meta not an object' => [['meta' => 'nope']],
])->throws(ValidationException::class);

it('suspends a user with a reason', function (): void {
    $user = app(SuspendUserAction::class)->execute(user(), new SuspendUserData(reason: 'Chargeback'));

    expect($user->rosterStatus())->toBe(UserStatus::Suspended)
        ->and($user->rosterProfile?->status_reason)->toBe('Chargeback')
        ->and($user->rosterProfile?->status_changed_at)->not->toBeNull();
});

it('deactivates a suspended user', function (): void {
    $user = app(SuspendUserAction::class)->execute(user());

    expect(app(DeactivateUserAction::class)->execute($user)->rosterStatus())->toBe(UserStatus::Deactivated);
});

it('reactivates a user and clears the reason', function (): void {
    $user = app(DeactivateUserAction::class)->execute(user(), new DeactivateUserData(reason: 'Left'));

    $user = app(ReactivateUserAction::class)->execute($user);

    expect($user->rosterStatus())->toBe(UserStatus::Active)
        ->and($user->rosterProfile?->status_reason)->toBeNull();
});

it('refuses a transition to the current status', function (string $action): void {
    $user = user();

    if ($action !== ReactivateUserAction::class) {
        app($action)->execute($user);
    }

    app($action)->execute($user->refresh());
})->with([
    SuspendUserAction::class,
    DeactivateUserAction::class,
    ReactivateUserAction::class,
])->throws(ValidationException::class);

it('refuses to suspend or deactivate yourself', function (string $action): void {
    $user = user();

    app($action)->execute($user, actor: $user);
})->with([
    SuspendUserAction::class,
    DeactivateUserAction::class,
])->throws(ValidationException::class);
