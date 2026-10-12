<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Roster;

it('preloads the same current organization and team as resolving one user at a time', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    $sales = app(CreateTeamAction::class)->execute($globex, new CreateTeamData(name: 'Sales'));

    $users = [];

    // No memberships.
    $users[] = user();
    // Stored organization and seated team.
    $users[] = $u = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $u->getRouteKey()));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $u->getRouteKey()));
    app(SwitchContextAction::class)->execute($u, new SwitchContextData(organization: 'acme', team: 'ops'));
    // Falls back to the first organization after leaving the stored one.
    $users[] = $u = user();
    app(AddMemberAction::class)->execute($globex, new AddMemberData(user: $u->getRouteKey()));
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $u->getRouteKey()));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $u->getRouteKey()));
    app(SwitchContextAction::class)->execute($u, new SwitchContextData(organization: 'acme', team: 'ops'));
    app(RemoveMemberAction::class)->execute($acme, $u);
    // Stored team in another organization than the current one.
    $users[] = $u = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $u->getRouteKey()));
    app(AddMemberAction::class)->execute($globex, new AddMemberData(user: $u->getRouteKey()));
    app(AddTeamMemberAction::class)->execute($sales, new AddTeamMemberData(user: $u->getRouteKey()));
    ProfileModel::query()->where('user_id', $u->getKey())->update(['current_organization_id' => $acme->id, 'current_team_id' => $sales->id]);

    $expected = [];

    foreach ($users as $user) {
        app()->forgetScopedInstances();
        $fresh = $user->fresh();
        $expected[] = [app(Roster::class)->organization($fresh)?->slug, app(Roster::class)->team($fresh)?->slug];
    }

    app()->forgetScopedInstances();
    $fresh = array_map(fn ($user) => $user->fresh(['rosterProfile']), $users);
    app(Roster::class)->preload($fresh);

    expect(array_map(fn ($user) => [app(Roster::class)->organization($user)?->slug, app(Roster::class)->team($user)?->slug], $fresh))
        ->toBe($expected)
        ->and($expected)->toBe([[null, null], ['acme', 'ops'], ['globex', null], ['acme', null]]);
});
