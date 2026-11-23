<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Users;
use RefactorCircus\Roster\Tests\Fixtures\PlainUser;

it('gives a model without the trait a profile relation', function (): void {
    $user = PlainUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'x']);

    app(UpdateProfileAction::class)->execute($user, new UpdateProfileData(displayName: 'Countess'));
    app(SuspendUserAction::class)->execute($user);

    $fresh = PlainUser::query()->with('rosterProfile')->sole();

    expect($fresh->getRelation('rosterProfile'))->toBeInstanceOf(ProfileModel::class)
        ->and($fresh->getRelation('rosterProfile')->display_name)->toBe('Countess')
        ->and(app(Users::class)->status($fresh))->toBe(UserStatus::Suspended);
});

it('serves the HTTP API for a model without the trait', function (): void {
    PlainUser::query()->create(['name' => 'Ada', 'email' => 'ada@example.com', 'password' => 'x']);

    $this->getJson(route('roster.users.index'))->assertOk()->assertJsonPath('data.0.name', 'Ada');
});

it('deletes users permanently when the model has no SoftDeletes', function (): void {
    $doomed = PlainUser::query()->create(['name' => 'Doomed', 'email' => 'doomed@example.com', 'password' => 'secret123']);

    app(DeleteUserAction::class)->execute($doomed);

    expect(PlainUser::query()->whereKey($doomed->getKey())->exists())->toBeFalse();
});
