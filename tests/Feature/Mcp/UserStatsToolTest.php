<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Testing\StructuredSchema;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ShowUserStatsTool;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Support\Statuses;

it('is offered as show-user-stats-tool', function (): void {
    expect(RosterServer::TOOLS)->toContain(ShowUserStatsTool::class)
        ->and(app(ShowUserStatsTool::class)->name())->toBe('show-user-stats-tool');
});

it('shows the user stats with parity to the http payload', function (): void {
    user();
    user()->roster()->update(['status' => 'suspended']);
    user()->roster()->update(['status' => 'deactivated']);

    $http = $this->getJson(route('roster.stats.users'))->assertOk()->json();

    expect($http['data']['total'])->toBe(3);

    mcpTool(ShowUserStatsTool::class)->assertOk()->assertStructuredContent($http);
});

it('shows zeroes for an empty directory with parity to the http payload', function (): void {
    $http = $this->getJson(route('roster.stats.users'))->assertOk()->json();

    mcpTool(ShowUserStatsTool::class)->assertOk()->assertStructuredContent($http);
});

it('declares every configured status as a required count in its output schema', function (): void {
    $schema = app(ShowUserStatsTool::class)->toArray()['outputSchema'];
    $data = $schema['properties']['data'];
    $byStatus = $data['properties']['by_status'];

    expect($schema['required'])->toBe(['data'])
        ->and($data['required'])->toEqualCanonicalizing(['total', 'by_status'])
        ->and($data['properties']['total']['type'])->toBe('integer')
        ->and(array_keys($byStatus['properties']))->toEqualCanonicalizing(Statuses::values(Statuses::users()))
        ->and($byStatus['required'])->toEqualCanonicalizing(Statuses::values(Statuses::users()))
        ->and(collect($byStatus['properties'])->pluck('type')->unique()->all())->toBe(['integer']);

    user();

    expect(StructuredSchema::violations($schema, $this->getJson(route('roster.stats.users'))->json()))->toBe([]);
});
