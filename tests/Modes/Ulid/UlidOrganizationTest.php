<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Roster;

beforeEach(function (): void {
    config()->set('roster.users.columns', ['name' => 'full_name', 'email' => 'email_address', 'password' => 'secret']);
});

it('runs organizations on ulid user keys', function (): void {
    $owner = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Ada', email: 'ada@example.com'));
    $member = app(CreateUserAction::class)->execute(new CreateUserData(name: 'Grace', email: 'grace@example.com'));

    $organization = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Acme', owner: $owner->getRouteKey()));
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $member->getRouteKey()));

    expect($organization->isOwnedBy($owner))->toBeTrue()
        ->and($organization->members()->count())->toBe(2)
        ->and(app(Roster::class)->organization($member)?->is($organization))->toBeTrue();

    $this->getJson(route('roster.organizations.members.index', 'acme'))
        ->assertOk()
        ->assertJsonPath('data.1.user.email', 'grace@example.com');
});
