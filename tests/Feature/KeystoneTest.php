<?php

declare(strict_types=1);

use Laravel\Mcp\Server\Transport\FakeTransporter;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Mcp\Tools\ListRosterHistoryTool;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\RosterServiceProvider;

it('registers Roster with refactor-circus/keystone', function (): void {
    $package = app(PackageRegistry::class)->get('roster');

    expect($package->label)->toBe('Roster')
        ->and($package->server)->toBe(RosterServer::class)
        ->and($package->authorization)->toBeTrue()
        ->and(app(PackageRegistry::class)->for(RosterServiceProvider::class))->toBe($package);
});

it('answers the history route with 404 while no shared audit log is installed', function (): void {
    $this->getJson(route('roster.history.index'))
        ->assertNotFound()
        ->assertJsonPath('message', 'No audit log is installed. Install refactor-circus/keen to record history.');
});

it('declares the history tool, but offers it only while Roster audits', function (): void {
    expect(RosterServer::TOOLS)->toContain(ListRosterHistoryTool::class)
        ->and(app(ListRosterHistoryTool::class)->name())->toBe('list-roster-history-tool')
        ->and(app(Roster::class)->auditing())->toBeFalse()
        ->and((new RosterServer(new FakeTransporter))->createContext()->tools()->contains(fn (mixed $tool): bool => $tool instanceof ListRosterHistoryTool))->toBeFalse();
});
