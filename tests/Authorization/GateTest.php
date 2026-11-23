<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

it('answers can() for roster abilities in the given scope', function (): void {
    $acme = organization();
    $globex = organization(attributes: ['name' => 'Globex']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    grant($ada, roleGranting(['roster.teams.manage'], 'organization'), $acme);

    expect($ada->can('roster.teams.manage', $acme))->toBeTrue()
        ->and($ada->can('roster.teams.manage', $globex))->toBeFalse()
        // A team argument checks within its organization.
        ->and($ada->can('roster.teams.manage', app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'))))->toBeTrue();
});

it('leaves abilities Roster does not declare to the app, even when a role grants them', function (): void {
    $acme = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    grant($ada, roleGranting(['invoices.edit'], 'organization'), $acme);

    expect($ada->can('invoices.edit', $acme))->toBeFalse();

    Gate::define('invoices.edit', fn (): bool => true);

    expect($ada->can('invoices.edit', $acme))->toBeTrue();
});

it('uses the current context when no scope is given', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    grant($ada, 'member', $acme);
    app(SwitchContextAction::class)->execute($ada, new SwitchContextData(organization: 'acme'));

    expect($ada->can('roster.members.view'))->toBeTrue()
        ->and($ada->can('roster.members.manage'))->toBeFalse();
});

it('leaves unknown abilities to the app', function (): void {
    Gate::define('publish-post', fn (): bool => true);
    $ada = user();

    expect($ada->can('publish-post'))->toBeTrue()
        ->and($ada->can('something-undefined'))->toBeFalse();
});

it('leaves super-admins to the bound implementation, and other gates to the app', function (): void {
    Gate::define('publish-post', fn (): bool => false);
    $ada = user();
    grant($ada, 'super-admin');

    expect($ada->can('roster.users.view'))->toBeTrue()
        ->and($ada->can('publish-post'))->toBeFalse();
});

it('works in blade', function (): void {
    $ada = user();
    $this->actingAs($ada);

    expect(Blade::render("@can('roster.users.view') yes @else no @endcan"))->toContain('no');

    grant($ada, 'super-admin');

    expect(Blade::render("@can('roster.users.view') yes @else no @endcan"))->toContain('yes');
});

it('defines no viewAtrium gate, even for a user granted atrium.view', function (): void {
    // An install upgraded from v1 may still grant it through a role.
    $ada = user();
    grant($ada, roleGranting(['atrium.view']));

    expect(Gate::has('viewAtrium'))->toBeFalse()
        ->and(Gate::forUser($ada)->allows('viewAtrium'))->toBeFalse();
});

it('no longer treats atrium.view as global-only', function (): void {
    $owner = user();
    $acme = organization($owner);

    // An owner holds every ability that is not global-only in their organization.
    expect(OrganizationModel::GLOBAL_ONLY_ABILITIES)->toBe(['roster.users.*', 'roster.organizations.create'])
        ->and(app(Permissions::class)->allows($owner, 'atrium.view', $acme))->toBeTrue()
        ->and(app(Permissions::class)->allows($owner, 'roster.users.view', $acme))->toBeFalse();
});
