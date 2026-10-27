<?php

declare(strict_types=1);

use Laravel\Ai\Contracts\Tool as AgentTool;
use Laravel\Ai\Tools\Request;
use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Cortex\Domains\ConcreteServer\Actions\CreateMcpInstructionVersionAction;
use RefactorCircus\Cortex\Domains\ConcreteServer\Data\CreateMcpInstructionVersionData;
use RefactorCircus\Cortex\Domains\ConcreteServer\Services\McpServerRegistry;
use RefactorCircus\Cortex\Domains\Tool\Actions\CreateToolDescriptionVersionAction;
use RefactorCircus\Cortex\Domains\Tool\Data\CreateToolDescriptionVersionData;
use RefactorCircus\Cortex\Domains\Tool\Services\ToolRegistry;
use RefactorCircus\Keystone\Cortex\CortexIntegration;
use RefactorCircus\Keystone\Mcp\Server;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ListUsersTool;
use RefactorCircus\Roster\Mcp\RosterServer;

it('registers the MCP server with Cortex', function (): void {
    $servers = app(McpServerRegistry::class);

    expect($servers->has('roster'))->toBeTrue()
        ->and($servers->get('roster'))->toBe(RosterServer::class)
        ->and($servers->defaultInstructions('roster'))->toStartWith("Manage this application's users");
});

it('offers every Roster tool to Cortex agents under its own name, the history tool only while Roster audits', function (): void {
    $tools = app(ToolRegistry::class);

    $names = array_map(fn (string $class): string => app($class)->name(), Server::withoutHistory(RosterServer::TOOLS));

    expect(array_diff($names, $tools->names()))->toBe([])
        ->and($tools->names())->not->toContain('list-roster-history-tool')
        ->and($tools->get('list-users-tool'))->toBeInstanceOf(AgentTool::class)
        ->and($tools->tagsFor('list-users-tool'))->toContain('roster');
});

it('offers only the tools listed in config', function (): void {
    config()->set('roster.cortex.tools', ['list-users-tool', 'show-user-tool']);
    app()->forgetInstance(ToolRegistry::class);

    $tools = app(ToolRegistry::class);

    expect($tools->has('list-users-tool'))->toBeTrue()
        ->and($tools->has('show-user-tool'))->toBeTrue()
        ->and($tools->has('delete-user-tool'))->toBeFalse();
});

it('serves the instructions published in Cortex', function (): void {
    app(CreateMcpInstructionVersionAction::class)->execute('roster', new CreateMcpInstructionVersionData(content: 'Never delete users.', publish: true));

    expect((new RosterServer(new FakeTransporter))->createContext()->instructions)->toBe('Never delete users.');
});

it('serves tool descriptions published in Cortex, to MCP clients and agents alike', function (): void {
    app(CreateToolDescriptionVersionAction::class)->execute('list-users-tool', new CreateToolDescriptionVersionData(content: 'List our staff.', publish: true));

    expect(app(ListUsersTool::class)->description())->toBe('List our staff.')
        ->and(app(ToolRegistry::class)->get('list-users-tool')->description())->toBe('List our staff.');
});

it('lets an agent call a tool as the signed-in user, with Roster permissions applied', function (): void {
    $ada = user(['name' => 'Ada']);
    $tool = app(ToolRegistry::class)->get('list-users-tool');

    // No one signed in: refused.
    expect((string) $tool->handle(new Request([])))->toContain('Unauthorized.');

    $this->actingAs($ada);

    expect((string) $tool->handle(new Request([])))->toContain('Unauthorized.');

    grant($ada, 'super-admin');

    expect((string) $tool->handle(new Request([])))->toContain('Ada');
});

it('stays out of Cortex when turned off', function (): void {
    $integration = CortexIntegration::for(app(PackageRegistry::class)->get('roster'));

    expect($integration->active())->toBeTrue();

    config()->set('roster.cortex.enabled', false);

    expect($integration->active())->toBeFalse()
        ->and($integration->instructions())->toBeNull()
        ->and($integration->description('list-users-tool'))->toBeNull();
});
