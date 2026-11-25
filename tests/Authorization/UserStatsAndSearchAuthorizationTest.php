<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use RefactorCircus\Roster\Domains\User\Mcp\Tools\ShowUserStatsTool;

/**
 * The two user directory routes, with a query each accepts.
 *
 * @return array<string, array{0: string, 1: array<string, mixed>}>
 */
function userDirectoryRoutes(): array
{
    return [
        'stats/users' => ['roster.stats.users', []],
        'search/users' => ['roster.search.users', ['q' => 'Ada']],
    ];
}

it('rejects guests with 401', function (string $name, array $query): void {
    $this->getJson(route($name, $query))->assertUnauthorized();
})->with(userDirectoryRoutes());

it('rejects a signed-in user without roster.users.view with 403', function (string $name, array $query): void {
    $this->actingAs(user())->getJson(route($name, $query))->assertForbidden();
})->with(userDirectoryRoutes());

it('rejects roster.users.view held only in an organization with 403', function (string $name, array $query): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $viewer = user();
    grant($viewer, roleGranting(['roster.users.view'], 'organization'), $acme);

    $this->actingAs($viewer)->getJson(route($name, $query))->assertForbidden();
})->with(userDirectoryRoutes());

it('answers a user holding global roster.users.view', function (string $name, array $query): void {
    $viewer = user(['name' => 'Ada Lovelace']);
    grant($viewer, roleGranting(['roster.users.view']));

    $this->actingAs($viewer)->getJson(route($name, $query))->assertOk()->assertJsonStructure(['data']);
})->with(userDirectoryRoutes());

it('needs global roster.users.view for show-user-stats-tool', function (): void {
    mcpTool(ShowUserStatsTool::class)->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user());
    mcpTool(ShowUserStatsTool::class)->assertHasErrors(['Unauthorized.']);

    $viewer = user();
    grant($viewer, roleGranting(['roster.users.view']));
    $this->actingAs($viewer);

    mcpTool(ShowUserStatsTool::class)->assertOk()->assertSee('by_status');
});
