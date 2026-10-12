<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;

it('creates, shows, updates and deletes an organization', function (): void {
    $owner = user();

    $this->postJson(route('roster.organizations.store'), ['name' => 'Acme', 'owner' => $owner->getRouteKey(), 'domains' => ['acme.com']])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'acme')
        ->assertJsonPath('data.owner', $owner->getRouteKey())
        ->assertJsonPath('data.domains', ['acme.com'])
        ->assertJsonPath('data.members_count', 1);

    $this->getJson(route('roster.organizations.show', 'acme'))->assertOk()->assertJsonPath('data.name', 'Acme');

    $this->patchJson(route('roster.organizations.update', 'acme'), ['auto_join' => true])
        ->assertOk()
        ->assertJsonPath('data.auto_join', true);

    $this->getJson(route('roster.organizations.index'))->assertOk()->assertJsonPath('meta.total', 1);

    $this->deleteJson(route('roster.organizations.destroy', 'acme'))->assertNoContent();
    $this->getJson(route('roster.organizations.show', 'acme'))->assertNotFound();
});

it('manages members and ownership', function (): void {
    $organization = organization();
    $ada = user();

    $this->postJson(route('roster.organizations.members.store', $organization->slug), ['user' => $ada->getRouteKey()])
        ->assertCreated()
        ->assertJsonPath('data.user.id', $ada->getRouteKey())
        ->assertJsonPath('data.owner', false)
        ->assertJsonPath('data.source', 'direct');

    $this->getJson(route('roster.organizations.members.index', $organization->slug))->assertOk()->assertJsonPath('meta.total', 2);

    $this->postJson(route('roster.organizations.transfer', $organization->slug), ['user' => $ada->getRouteKey()])
        ->assertOk()
        ->assertJsonPath('data.owner', $ada->getRouteKey());

    $this->deleteJson(route('roster.organizations.members.destroy', [$organization->slug, $ada->getRouteKey()]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user');
});

it('manages teams and seats', function (): void {
    $organization = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($organization, new AddMemberData(user: $ada->getRouteKey()));

    $this->postJson(route('roster.organizations.teams.store', $organization->slug), ['name' => 'Ops'])
        ->assertCreated()
        ->assertJsonPath('data.slug', 'ops')
        ->assertJsonPath('data.organization', $organization->slug);

    $this->postJson(route('roster.organizations.teams.members.store', [$organization->slug, 'ops']), ['user' => $ada->getRouteKey()])
        ->assertOk()
        ->assertJsonPath('data.members.0.id', $ada->getRouteKey());

    $this->patchJson(route('roster.organizations.teams.update', [$organization->slug, 'ops']), ['name' => 'Operations'])
        ->assertOk()
        ->assertJsonPath('data.name', 'Operations');

    $this->getJson(route('roster.organizations.teams.index', $organization->slug))->assertOk()->assertJsonPath('data.0.members_count', 1);

    $this->deleteJson(route('roster.organizations.teams.members.destroy', [$organization->slug, 'ops', $ada->getRouteKey()]))->assertNoContent();
    $this->deleteJson(route('roster.organizations.teams.destroy', [$organization->slug, 'ops']))->assertNoContent();
    $this->getJson(route('roster.organizations.teams.show', [$organization->slug, 'ops']))->assertNotFound();
});

it('switches context and runs domain join', function (): void {
    $ada = user(['email' => 'ada@acme.com']);
    organization(attributes: ['name' => 'Acme', 'domains' => ['acme.com'], 'auto_join' => true]);

    $this->postJson(route('roster.users.domain-join', $ada->getRouteKey()))
        ->assertOk()
        ->assertJsonPath('data.0.slug', 'acme');

    $this->putJson(route('roster.users.context.update', $ada->getRouteKey()), ['organization' => 'acme'])
        ->assertOk()
        ->assertJsonPath('data.current_organization', 'acme')
        ->assertJsonPath('data.current_team', null);
});
