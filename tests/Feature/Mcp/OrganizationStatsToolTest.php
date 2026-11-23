<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Testing\StructuredSchema;
use RefactorCircus\Roster\Domains\Organization\Actions\DeleteOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ShowOrganizationStatsTool;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Mcp\RosterServer;

it('is offered as show-organization-stats-tool', function (): void {
    expect(RosterServer::TOOLS)->toContain(ShowOrganizationStatsTool::class)
        ->and(app(ShowOrganizationStatsTool::class)->name())->toBe('show-organization-stats-tool');
});

it('shows the organization stats with parity to the http payload', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    organization(attributes: ['name' => 'Globex']);
    app(DeleteOrganizationAction::class)->execute(organization(attributes: ['name' => 'Initech']));
    app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    invitationToken($acme);
    invitationToken($acme, attributes: ['revoked_at' => now()]);

    $http = $this->getJson(route('roster.stats.organizations'))->assertOk()->json();

    expect($http['data'])->toBe(['organizations' => 2, 'teams' => 1, 'pending_invitations' => 1]);

    mcpTool(ShowOrganizationStatsTool::class)->assertOk()->assertStructuredContent($http);
});

it('shows zeroes with parity to the http payload', function (): void {
    $http = $this->getJson(route('roster.stats.organizations'))->assertOk()->json();

    mcpTool(ShowOrganizationStatsTool::class)->assertOk()->assertStructuredContent($http);
});

it('declares the three counts as required integers in its output schema', function (): void {
    $schema = app(ShowOrganizationStatsTool::class)->toArray()['outputSchema'];
    $data = $schema['properties']['data'];

    expect($schema['required'])->toBe(['data'])
        ->and($data['required'])->toEqualCanonicalizing(['organizations', 'teams', 'pending_invitations'])
        ->and(collect($data['properties'])->pluck('type')->unique()->all())->toBe(['integer']);

    organization();

    expect(StructuredSchema::violations($schema, $this->getJson(route('roster.stats.organizations'))->json()))->toBe([]);
});
