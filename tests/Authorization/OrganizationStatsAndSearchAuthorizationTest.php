<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ShowOrganizationStatsTool;

/**
 * The two organization directory routes, with a query each accepts.
 *
 * @return array<string, array{0: string, 1: array<string, mixed>}>
 */
function organizationDirectoryRoutes(): array
{
    return [
        'stats/organizations' => ['roster.stats.organizations', []],
        'search/organizations' => ['roster.search.organizations', ['q' => 'A']],
    ];
}

it('rejects guests with 401', function (string $name, array $query): void {
    $this->getJson(route($name, $query))->assertUnauthorized();
})->with(organizationDirectoryRoutes());

it('rejects a signed-in user without roster.organizations.view with 403', function (string $name, array $query): void {
    organization(attributes: ['name' => 'Acme']);

    $this->actingAs(user())->getJson(route($name, $query))->assertForbidden();
})->with(organizationDirectoryRoutes());

it('answers a user holding global roster.organizations.view', function (string $name, array $query): void {
    organization(attributes: ['name' => 'Acme']);
    $viewer = user();
    grant($viewer, roleWith(['roster.organizations.view'])->slug);

    $this->actingAs($viewer)->getJson(route($name, $query))->assertOk()->assertJsonStructure(['data']);
})->with(organizationDirectoryRoutes());

it('needs global roster.organizations.view for the stats', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $viewer = user();
    grant($viewer, roleWith(['roster.organizations.view'], 'organization')->slug, $acme);

    $this->actingAs($viewer)->getJson(route('roster.stats.organizations'))->assertForbidden();
});

it('searches only the organizations the caller may view there', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $acorn = organization(attributes: ['name' => 'Acorn']);
    organization(attributes: ['name' => 'Acropolis']);
    $viewer = user();
    $role = roleWith(['roster.organizations.view'], 'organization');
    grant($viewer, $role->slug, $acme);
    grant($viewer, $role->slug, $acorn);

    $this->actingAs($viewer);

    expect(collect($this->getJson(route('roster.search.organizations', ['q' => 'Ac']))->assertOk()->json('data'))->pluck('slug')->all())
        ->toBe(['acme', 'acorn']);

    // A permission held in one organization opens no other.
    $other = user();
    grant($other, roleWith(['roster.teams.view'], 'organization')->slug, $acme);

    $this->actingAs($other)->getJson(route('roster.search.organizations', ['q' => 'Ac']))->assertForbidden();
});

it('searches every organization for a caller holding the permission globally', function (): void {
    organization(attributes: ['name' => 'Acme']);
    organization(attributes: ['name' => 'Acorn']);
    $viewer = user();
    grant($viewer, roleWith(['roster.organizations.view'])->slug);

    expect(collect($this->actingAs($viewer)->getJson(route('roster.search.organizations', ['q' => 'Ac']))->assertOk()->json('data'))->pluck('slug')->all())
        ->toBe(['acme', 'acorn']);
});

it('needs global roster.organizations.view for show-organization-stats-tool', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    mcpTool(ShowOrganizationStatsTool::class)->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user());
    mcpTool(ShowOrganizationStatsTool::class)->assertHasErrors(['Unauthorized.']);

    $local = user();
    grant($local, roleWith(['roster.organizations.view'], 'organization')->slug, $acme);
    $this->actingAs($local);
    mcpTool(ShowOrganizationStatsTool::class)->assertHasErrors(['Unauthorized.']);

    $viewer = user();
    grant($viewer, roleWith(['roster.organizations.view'])->slug);
    $this->actingAs($viewer);

    mcpTool(ShowOrganizationStatsTool::class)->assertOk()->assertSee('pending_invitations');
});
