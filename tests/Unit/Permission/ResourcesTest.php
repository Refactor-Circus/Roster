<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Models\UserModel;

it('is bound as a singleton', function (): void {
    expect(app(Resources::class))->toBe(app(Resources::class));
});

it('registers the six core resources under roster.*, before any boot()', function (): void {
    expect(Authorizer::RESOURCES)->toBe([
        'users' => UserModel::class,
        'organizations' => OrganizationModel::class,
        'members' => MembershipModel::class,
        'teams' => TeamModel::class,
        'invitations' => InvitationModel::class,
        'roles' => RoleModel::class,
    ]);

    $resources = app(Resources::class);

    foreach (Authorizer::RESOURCES as $resource => $model) {
        expect($resources->model('roster.'.$resource))->toBe($model);
    }
});

it('registers a model for a prefix and returns itself', function (): void {
    $resources = new Resources;

    expect($resources->resource('acme.widgets', OrganizationModel::class))->toBe($resources)
        ->and($resources->model('acme.widgets'))->toBe(OrganizationModel::class)
        ->and($resources->model('acme.gadgets'))->toBeNull()
        ->and($resources->all())->toBe(['acme.widgets' => OrganizationModel::class]);
});

it('accepts valid prefixes', function (string $prefix): void {
    $resources = (new Resources)->resource($prefix, OrganizationModel::class);

    expect($resources->model($prefix))->toBe(OrganizationModel::class);
})->with(['a.b', 'a-1.b-2']);

it('rejects invalid prefixes, naming them', function (string $prefix): void {
    expect(fn () => (new Resources)->resource($prefix, OrganizationModel::class))
        ->toThrow(InvalidArgumentException::class, "[{$prefix}]");
})->with([
    'empty' => '',
    'one segment' => 'a',
    'three segments' => 'a.b.c',
    'upper case' => 'A.b',
    'empty segment' => 'a..b',
    'trailing dot' => 'a.b.',
]);

it('rejects a prefix registered twice, naming it', function (): void {
    $resources = (new Resources)->resource('acme.widgets', OrganizationModel::class);

    expect(fn () => $resources->resource('acme.widgets', TeamModel::class))
        ->toThrow(InvalidArgumentException::class, '[acme.widgets]')
        ->and($resources->model('acme.widgets'))->toBe(OrganizationModel::class);
});

it('reserves roster.* for Roster, naming the prefix', function (string $prefix): void {
    expect(fn () => app(Resources::class)->resource($prefix, OrganizationModel::class))
        ->toThrow(InvalidArgumentException::class, "[{$prefix}]");
})->with([
    'any roster prefix' => 'roster.anything',
    'a core prefix' => 'roster.users',
]);

it('says which prefixes are Roster\'s own', function (): void {
    $resources = new Resources;

    expect($resources->reserved('roster.users'))->toBeTrue()
        ->and($resources->reserved('roster.anything'))->toBeTrue()
        ->and($resources->reserved('acme.widgets'))->toBeFalse()
        ->and($resources->reserved('rosters.widgets'))->toBeFalse();
});

it('takes Roster\'s own prefixes through the internal method only', function (): void {
    $resources = new Resources;

    expect($resources->rosterResource('roster.anything', OrganizationModel::class))->toBe($resources)
        ->and($resources->model('roster.anything'))->toBe(OrganizationModel::class)
        ->and(fn () => $resources->rosterResource('acme.widgets', OrganizationModel::class))
        ->toThrow(InvalidArgumentException::class, '[acme.widgets]')
        ->and(fn () => $resources->rosterResource('roster.anything', TeamModel::class))
        ->toThrow(InvalidArgumentException::class, '[roster.anything]')
        ->and(fn () => $resources->rosterResource('roster..x', TeamModel::class))
        ->toThrow(InvalidArgumentException::class, '[roster..x]');
});

it('rejects a class that is not an Eloquent model, naming the prefix', function (): void {
    expect(fn () => (new Resources)->resource('acme.widgets', stdClass::class))
        ->toThrow(InvalidArgumentException::class, '[acme.widgets]');
});

it('bumps its generation on every registration', function (): void {
    $resources = new Resources;
    $start = $resources->generation();

    $resources->resource('acme.widgets', OrganizationModel::class);
    $resources->rosterResource('roster.anything', TeamModel::class);

    expect($resources->generation())->toBe($start + 2);

    try {
        $resources->resource('acme.widgets', OrganizationModel::class);
    } catch (InvalidArgumentException) {
        // A refused registration changes nothing.
    }

    expect($resources->generation())->toBe($start + 2);
});
