<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\ListTeamsAction;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\ShowTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatedActionEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatedEvent;
use RefactorCircus\Roster\Domains\Team\Events\TeamCreatingActionEvent;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

it('creates teams with slugs unique within the organization', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);

    $first = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    $second = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    $elsewhere = app(CreateTeamAction::class)->execute($globex, new CreateTeamData(name: 'Ops'));

    expect([$first->slug, $second->slug, $elsewhere->slug])->toBe(['ops', 'ops-2', 'ops']);

    expect(validator(['name' => 'X', 'slug' => 'ops'], CreateTeamData::rules($acme))->fails())->toBeTrue()
        ->and(validator(['name' => 'X', 'slug' => 'ops'], CreateTeamData::rules(organization()))->passes())->toBeTrue();
});

it('fires no finished event for a write that rolls back', function (): void {
    $organization = organization();
    $seen = [];

    Event::listen([TeamCreatingActionEvent::class, TeamCreatedActionEvent::class], function (object $event) use (&$seen): void {
        $seen[] = class_basename($event);
    });

    // The action's own transaction fails part-way.
    Event::listen(TeamCreatedEvent::class, fn () => throw new RuntimeException('failed'));

    expect(fn () => app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops')))->toThrow(RuntimeException::class);

    Event::forget(TeamCreatedEvent::class);

    // A surrounding transaction rolls back after the action returned.
    try {
        DB::transaction(function () use ($organization): void {
            app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));

            throw new RuntimeException('rolled back');
        });
    } catch (RuntimeException) {
    }

    expect($seen)->toBe(['TeamCreatingActionEvent', 'TeamCreatingActionEvent'])
        ->and(TeamModel::query()->count())->toBe(0);
});

it('lists, shows, updates and deletes teams', function (): void {
    $organization = organization();
    $team = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));

    expect(app(ListTeamsAction::class)->execute($organization)->total())->toBe(1);

    $team = app(UpdateTeamAction::class)->execute($team, new UpdateTeamData(name: 'Operations', slug: 'operations'));

    expect($team->slug)->toBe('operations')
        ->and(app(ShowTeamAction::class)->execute($team)->relationLoaded('memberships'))->toBeTrue();

    app(DeleteTeamAction::class)->execute($team);

    expect(TeamModel::query()->count())->toBe(0);
});

it('seats only organization members, once', function (): void {
    $organization = organization();
    $team = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));
    $ada = user();

    expect(fn () => app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $ada->getRouteKey())))->toThrow(ValidationException::class);

    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));
    $team = app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $ada->getRouteKey()));

    expect($team->hasMember($ada))->toBeTrue()
        ->and(fn () => app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $ada->getRouteKey())))->toThrow(ValidationException::class);

    $team = app(RemoveTeamMemberAction::class)->execute($team, $ada);

    expect($team->hasMember($ada))->toBeFalse()
        ->and($organization->membershipFor($ada))->not->toBeNull()
        ->and(fn () => app(RemoveTeamMemberAction::class)->execute($team, $ada))->toThrow(ValidationException::class);
});

it('lists one team\'s members through the member list, and shows a team with its first members', function (): void {
    $owner = user();
    $acme = organization($owner, ['name' => 'Acme']);
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Sales'));
    $ada = user(['name' => 'Ada']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada));
    $this->actingAs($owner);

    $this->getJson(route('roster.organizations.members.index', ['acme', 'team' => 'ops']))
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.user.name', 'Ada');
    $this->getJson(route('roster.organizations.members.index', ['acme', 'team' => 'sales']))->assertOk()->assertJsonCount(0, 'data');
    $this->getJson(route('roster.organizations.members.index', ['acme', 'team' => 'nope']))->assertUnprocessable()->assertJsonValidationErrors('team');

    expect(ShowTeamAction::MEMBERS)->toBe(100)
        ->and(app(ShowTeamAction::class)->execute($ops)->memberships->pluck('user_id')->all())->toBe([$ada->getKey()]);
});
