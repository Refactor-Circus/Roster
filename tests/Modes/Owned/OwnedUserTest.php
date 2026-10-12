<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Models\UserModel;
use RefactorCircus\Roster\Support\Users;

beforeEach(function (): void {
    config()->set('roster.users.model', UserModel::class);
});

it('manages users through the roster-owned model', function (): void {
    $user = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Ada', email: 'ada@example.com'));

    expect($user)->toBeInstanceOf(UserModel::class)
        ->and(app(Users::class)->model())->toBe(UserModel::class)
        ->and($user->roster()->exists)->toBeTrue()
        ->and($user->isRosterActive())->toBeTrue();
});

it('publishes the users migration only under its own tag', function (): void {
    $published = fn (string $tag): array => array_map(
        'basename',
        array_keys(ServiceProvider::pathsToPublish(null, $tag)),
    );

    expect($published('roster-users-migration'))->toBe(['users'])
        ->and($published('roster'))->not->toContain('users')
        ->and(Artisan::all())->toHaveKey('vendor:publish');
});

it('publishes the optional lower(email) index migration only under its own tag', function (): void {
    $published = fn (string $tag): array => array_map(
        'basename',
        array_keys(ServiceProvider::pathsToPublish(null, $tag)),
    );

    expect($published('roster-email-index-migration'))->toBe(['email-index'])
        ->and($published('roster'))->not->toContain('email-index');
});
