<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Authorization\Contracts\Scope;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

it('makes organizations and teams Keystone scopes', function (): void {
    expect(new OrganizationModel)->toBeInstanceOf(Scope::class)
        ->and(new TeamModel)->toBeInstanceOf(Scope::class);
});

it('puts a team inside its organization and an organization at the top', function (): void {
    $acme = organization();
    $team = TeamModel::query()->create(['organization_id' => $acme->getKey(), 'name' => 'Ops', 'slug' => 'ops']);

    expect($team->parentScope())->toBeInstanceOf(OrganizationModel::class)
        ->and($team->parentScope()?->getKey())->toBe($acme->getKey())
        ->and($acme->parentScope())->toBeNull();
});

it('lets an organization grant its owner every ability but the global-only ones', function (): void {
    $owner = user();
    $acme = organization($owner);

    expect($acme->grantsByItself($owner, 'roster.teams.manage'))->toBeTrue()
        ->and($acme->grantsByItself($owner, 'roster.members.manage'))->toBeTrue()
        ->and($acme->grantsByItself($owner, 'acme.reports.view'))->toBeTrue()
        ->and($acme->grantsByItself($owner, 'roster.users.view'))->toBeFalse()
        ->and($acme->grantsByItself($owner, 'roster.users.impersonate'))->toBeFalse()
        ->and($acme->grantsByItself($owner, 'roster.organizations.create'))->toBeFalse();
});

it('grants nothing by itself to a user who does not own the organization', function (): void {
    $acme = organization(user());
    $stranger = user();

    expect($acme->grantsByItself($stranger, 'roster.teams.manage'))->toBeFalse()
        ->and($acme->grantsByItself($stranger, 'roster.organizations.view'))->toBeFalse();
});

it('grants nothing by itself on a team, not even to the organization owner', function (): void {
    $owner = user();
    $acme = organization($owner);
    $team = TeamModel::query()->create(['organization_id' => $acme->getKey(), 'name' => 'Ops', 'slug' => 'ops']);

    expect($team->grantsByItself($owner, 'roster.teams.manage'))->toBeFalse()
        ->and($team->grantsByItself(user(), 'roster.teams.view'))->toBeFalse();
});

it('keeps the global-only abilities as Str::is patterns, with the old values', function (): void {
    expect(OrganizationModel::GLOBAL_ONLY_ABILITIES)->toBe(['roster.users.*', 'roster.organizations.create']);
});
