<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Testing\FakePermissions;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\SwitchContextAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Data\SwitchContextData;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Support\Authorization\Authorizer;
use RefactorCircus\Roster\Support\Authorization\ImpersonationAwarePermissions;
use RefactorCircus\Roster\Support\Authorization\Resources;

beforeEach(function (): void {
    config()->set('roster.authorization', true);
});

it('lives in Support\Authorization, without the roles resource', function (): void {
    expect(class_exists('RefactorCircus\Roster\Domains\Permission\Services\Authorizer'))->toBeFalse()
        ->and(class_exists('RefactorCircus\Roster\Domains\Permission\Services\Resources'))->toBeFalse()
        ->and(array_keys(Authorizer::RESOURCES))->toBe(['users', 'organizations', 'members', 'teams', 'invitations'])
        ->and(app(Resources::class)->model('roster.roles'))->toBeNull()
        ->and(app(Resources::class)->model('roster.teams'))->not->toBeNull();
});

it('decorates the bound permissions with the impersonation block', function (): void {
    expect(app(Permissions::class))->toBeInstanceOf(ImpersonationAwarePermissions::class);
});

it('answers permits() and check() from the bound permissions, in an organization and its teams', function (): void {
    $ada = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    $fake = FakePermissions::install();
    $authorizer = app(Authorizer::class);

    expect($authorizer->permits($ada, 'roster.teams.manage', $acme))->toBeFalse()
        ->and($authorizer->check($ada, 'roster.teams.manage', $acme))->toBeFalse();

    $fake->grant($ada, 'roster.teams.manage', $acme);

    expect($authorizer->permits($ada, 'roster.teams.manage', $acme))->toBeTrue()
        ->and($authorizer->check($ada, 'roster.teams.manage', $acme))->toBeTrue()
        // A grant in the organization counts on its teams, through the team's parent scope.
        ->and($authorizer->permits($ada, 'roster.teams.manage', $ops))->toBeTrue()
        ->and($authorizer->check($ada, 'roster.teams.manage', $ops))->toBeTrue()
        // Not in another organization, nor globally.
        ->and($authorizer->permits($ada, 'roster.teams.manage', $globex))->toBeFalse()
        ->and($authorizer->check($ada, 'roster.teams.manage', $globex))->toBeFalse()
        ->and($authorizer->permits($ada, 'roster.teams.manage'))->toBeFalse()
        // Everyone may act on themselves where the request says so.
        ->and($authorizer->permits($ada, 'roster.users.update', null, $ada))->toBeTrue()
        ->and($authorizer->permits($ada, 'roster.users.update', null, user()))->toBeFalse()
        // A guest holds nothing.
        ->and($authorizer->permits(null, 'roster.teams.manage', $acme))->toBeFalse();
});

it('lets an owner act in their organization through the Scope rule, but not on global-only abilities', function (): void {
    $owner = user();
    $acme = organization($owner, ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    FakePermissions::install();
    $authorizer = app(Authorizer::class);

    expect($authorizer->permits($owner, 'roster.teams.manage', $acme))->toBeTrue()
        ->and($authorizer->check($owner, 'roster.teams.manage', $acme))->toBeTrue()
        ->and($authorizer->check($owner, 'roster.organizations.update', $acme))->toBeTrue()
        ->and($authorizer->permits($owner, 'roster.teams.manage', $globex))->toBeFalse()
        ->and($authorizer->permits($owner, 'roster.users.view', $acme))->toBeFalse()
        ->and($authorizer->check($owner, 'roster.users.view', $acme))->toBeFalse();
});

it('combines the scopes the bound permissions name with the organizations the actor owns', function (): void {
    $ada = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization($ada, ['name' => 'Globex']);
    organization(attributes: ['name' => 'Initech']);
    $fake = FakePermissions::install()
        ->grant($ada, 'roster.members.view', $acme)
        ->grant($ada, 'roster.users.view', $acme);
    $authorizer = app(Authorizer::class);

    expect($authorizer->organizationsWith($ada, 'roster.members.view'))->toEqualCanonicalizing([$acme->getKey(), $globex->getKey()])
        // Owning an organization brings no global-only ability.
        ->and($authorizer->organizationsWith($ada, 'roster.users.view'))->toBe([$acme->getKey()])
        ->and($authorizer->organizationsWith($ada, 'roster.organizations.create'))->toBe([])
        ->and($authorizer->organizationsWith(null, 'roster.members.view'))->toBe([]);

    // Held globally: the contract answers null, and no organization limits the actor.
    $fake->grant($ada, 'roster.members.view');

    expect($authorizer->organizationsWith($ada, 'roster.members.view'))->toBeNull();
});

it('asks the contract with the organization scope type', function (): void {
    $ada = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    $fake = FakePermissions::install()->grant($ada, 'roster.members.view', $acme);

    expect($fake->scopesWith($ada, 'roster.members.view', (new OrganizationModel)->getMorphClass()))->toBe([$acme->getKey()])
        ->and(app(Authorizer::class)->organizationsWith($ada, 'roster.members.view'))->toBe([$acme->getKey()]);
});

it('blocks abilities on any bound implementation while impersonating, including one bound after boot', function (): void {
    $admin = user(['name' => 'Admin']);
    $ada = user(['name' => 'Ada']);
    $acme = organization(attributes: ['name' => 'Acme']);

    // Bound after Roster booted, as a role store's binding would be.
    $fake = (new FakePermissions)
        ->superAdmin($admin)
        ->grant($ada, 'acme.roles.manage')
        ->grant($ada, 'acme.widgets.view')
        ->grant($ada, 'acme.roles.assign', $acme);
    app()->singleton(Permissions::class, fn (): FakePermissions => $fake);

    // Before impersonating, the decorator passes every answer through.
    expect(app(Permissions::class))->toBeInstanceOf(ImpersonationAwarePermissions::class)
        ->and(app(Permissions::class)->allows($ada, 'acme.roles.manage'))->toBeTrue();

    $this->actingAs($admin);
    $started = app(StartImpersonationAction::class)->execute($ada, new StartImpersonationData(reason: 'Ticket 42'), $admin);
    $this->get($started->url);

    // The default blocked list holds `*.roles.manage` and `*.roles.assign`.
    $permissions = app(Permissions::class);

    expect($permissions)->toBeInstanceOf(ImpersonationAwarePermissions::class)
        ->and($permissions->allows($ada, 'acme.roles.manage'))->toBeFalse()
        ->and($permissions->allows($ada, 'acme.roles.assign', $acme))->toBeFalse()
        ->and($permissions->scopesWith($ada, 'acme.roles.assign', (new OrganizationModel)->getMorphClass()))->toBe([])
        ->and($permissions->scopesWith($ada, 'acme.roles.manage', (new OrganizationModel)->getMorphClass()))->toBe([])
        ->and($permissions->abilities($ada))->toBe(['acme.widgets.view'])
        // Abilities nothing blocks still pass.
        ->and($permissions->allows($ada, 'acme.widgets.view'))->toBeTrue();
});

it('answers roster abilities in the Gate from the bound permissions, with the context scope', function (): void {
    $ada = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    $globex = organization(attributes: ['name' => 'Globex']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    app(SwitchContextAction::class)->execute($ada, new SwitchContextData(organization: 'acme'));
    FakePermissions::install()->grant($ada, 'roster.members.view', $acme);

    expect($ada->can('roster.members.view'))->toBeTrue()
        ->and($ada->can('roster.members.view', $acme))->toBeTrue()
        ->and($ada->can('roster.members.view', $globex))->toBeFalse()
        ->and($ada->can('roster.members.manage'))->toBeFalse();
});

it('answers null rather than false, so the app still decides', function (): void {
    $ada = user();
    FakePermissions::install();
    Gate::define('roster.members.manage', fn (): bool => true);

    expect($ada->can('roster.members.manage'))->toBeTrue()
        ->and($ada->can('roster.members.view'))->toBeFalse();
});

it('leaves super-admins to the bound implementation and other abilities to the app', function (): void {
    $boss = user();
    FakePermissions::install()->superAdmin($boss);
    Gate::define('publish-post', fn (): bool => false);

    expect($boss->can('roster.users.view'))->toBeTrue()
        ->and(Gate::forUser($boss)->allows('roster.teams.manage', [organization(attributes: ['name' => 'Acme'])]))->toBeTrue()
        // Roster no longer passes super-admins on abilities it does not declare.
        ->and($boss->can('publish-post'))->toBeFalse()
        ->and($boss->can('roster.sso.view'))->toBeFalse();
});
