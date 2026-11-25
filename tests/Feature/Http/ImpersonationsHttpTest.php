<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools\ListImpersonationsTool;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools\StartImpersonationTool;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools\StopImpersonationTool;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;

// Impersonating compares what both users may do, which only a role store can
// list: without one only a super-admin may impersonate.
beforeEach(fn () => withRoleStore());

it('starts over HTTP, returning the link once', function (): void {
    $admin = user();
    $ada = user();
    $this->actingAs($admin);

    $response = $this->postJson(route('roster.users.impersonate', $ada->getRouteKey()), ['reason' => 'Ticket 42'])
        ->assertCreated()
        ->assertJsonPath('data.reason', 'Ticket 42')
        ->assertJsonPath('data.active', false)
        ->assertJsonMissingPath('data.token_hash');

    expect($response->json('url'))->toContain('/roster/impersonate/');

    $this->getJson(route('roster.impersonations.index'))
        ->assertOk()
        ->assertJsonPath('meta.total', 1)
        ->assertJsonMissingPath('url');

    $this->deleteJson(route('roster.impersonations.destroy', ImpersonationModel::query()->value('id')))
        ->assertOk()
        ->assertJsonPath('data.end_reason', 'stopped');
});

it('starts, lists and stops over MCP', function (): void {
    $admin = user();
    $ada = user();
    $this->actingAs($admin);

    mcpTool(StartImpersonationTool::class, ['user' => $ada->getRouteKey(), 'reason' => 'Ticket 42'])->assertOk()->assertSee('/roster/impersonate/');

    $http = test()->getJson(route('roster.impersonations.index'))->json();
    mcpTool(ListImpersonationsTool::class)->assertOk()->assertStructuredContent([
        'data' => $http['data'],
        'meta' => ['current_page' => 1, 'last_page' => 1, 'per_page' => 25, 'total' => 1],
    ]);

    mcpTool(StopImpersonationTool::class, ['impersonation' => ImpersonationModel::query()->value('id')])->assertOk()->assertSee('stopped');
});

it('needs a signed-in caller to start', function (): void {
    $this->postJson(route('roster.users.impersonate', user()->getRouteKey()), ['reason' => 'x y z'])->assertUnauthorized();
});
