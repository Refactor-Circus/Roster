<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Tests\Fixtures\UlidUser;

beforeEach(function (): void {
    config()->set('roster.users.columns', ['name' => 'full_name', 'email' => 'email_address', 'password' => 'secret']);
});

it('creates and manages a ulid-keyed user with mapped columns', function (): void {
    $user = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Ada', email: 'ada@example.com'));

    expect($user)->toBeInstanceOf(UlidUser::class)
        ->and($user->getAttribute('full_name'))->toBe('Ada')
        ->and($user->getAttribute('email_address'))->toBe('ada@example.com')
        ->and($user->getAttribute('secret'))->toBeString();

    app(SuspendUserAction::class)->execute($user);

    expect(UlidUser::query()->sole()->rosterStatus())->toBe(UserStatus::Suspended);
});

it('serves the HTTP API by ulid route key', function (): void {
    $user = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Ada', email: 'ada@example.com'));

    $this->getJson(route('roster.users.show', $user->getRouteKey()))
        ->assertOk()
        ->assertJsonPath('data.id', $user->getKey())
        ->assertJsonPath('data.name', 'Ada')
        ->assertJsonPath('data.email', 'ada@example.com');
});
