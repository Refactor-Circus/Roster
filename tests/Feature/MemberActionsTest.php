<?php

declare(strict_types=1);

use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamMemberModel;
use RefactorCircus\Roster\Roster;

it('adds and lists members', function (): void {
    $organization = organization();
    $ada = user();

    $membership = app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));

    expect($membership->user?->is($ada))->toBeTrue()
        ->and(app(ListMembersAction::class)->execute($organization)->total())->toBe(2);
});

it('refuses a duplicate member', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));

    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));
})->throws(ValidationException::class);

it('removes a member with their team seats and context', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));
    $team = app(CreateTeamAction::class)->execute($organization, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($team, new AddTeamMemberData(user: $ada->getRouteKey()));
    app(SwitchContextAction::class)->execute($ada, new SwitchContextData(organization: $organization->slug, team: 'ops'));

    app(RemoveMemberAction::class)->execute($organization, $ada);

    expect($organization->membershipFor($ada))->toBeNull()
        ->and(TeamMemberModel::query()->count())->toBe(0)
        ->and(app(Roster::class)->organization($ada))->toBeNull()
        ->and($ada->roster()->refresh()->current_organization_id)->toBeNull();
});

it('refuses to remove the owner or a non-member', function (bool $owner): void {
    $organization = organization();
    $user = $owner ? $organization->owner : user();

    app(RemoveMemberAction::class)->execute($organization, $user);
})->with(['owner' => true, 'stranger' => false])->throws(ValidationException::class);
