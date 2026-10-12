<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;

it('links, shows and unlinks external records on the settings tab', function (): void {
    $this->actingAs(user());
    organization(attributes: ['name' => 'Acme']);

    $this->post(route('atrium.roster.organizations.links.store', 'acme'), ['source' => 'erp', 'external_id' => 'C-1', 'account_number' => 'A-1'])
        ->assertRedirect(route('atrium.roster.organizations.show', ['acme', 'tab' => 'settings']));

    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'settings']))->assertOk()->assertSee('C-1')->assertSee('A-1');
    $this->get(route('atrium.roster.organizations.index', ['account_number' => 'A-1']))->assertOk()->assertSee('Acme')->assertSee('C-1');
    $this->get(route('atrium.roster.organizations.index', ['account_number' => 'nope']))->assertOk()->assertDontSee('Acme</a>', false);

    $this->delete(route('atrium.roster.organizations.links.destroy', ['acme', 'erp']))->assertRedirect();
    $this->get(route('atrium.roster.organizations.show', ['acme', 'tab' => 'settings']))->assertSee('Not linked to any external system.');
});

it('flags an organization with no owner', function (): void {
    $this->actingAs(user());
    app(SyncOrganizationAction::class)->execute(new SyncOrganizationData(source: 'erp', externalId: 'C-1', name: 'Initech'));

    $this->get(route('atrium.roster.organizations.show', 'initech'))->assertOk()->assertSee('This organization has no owner yet.');
});
