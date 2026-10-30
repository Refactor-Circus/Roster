<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use Laravel\Mcp\Server\McpServiceProvider;
use RefactorCircus\Roster\RosterServiceProvider;

/**
 * Roster as installed without the optional refactor-circus/atrium package:
 * Atrium's provider is not registered, and Roster skips its Atrium seam as
 * if Atrium's classes were absent.
 *
 * Atrium stays a dev dependency, so its classes still autoload here. This
 * mode proves the Atrium wiring is not registered; the `without-atrium` CI
 * job runs it with Atrium uninstalled.
 */
abstract class AtriumUnavailableTestCase extends TestCase
{
    protected function setUp(): void
    {
        // Before the application exists, so RosterServiceProvider::register() sees it.
        RosterServiceProvider::$withoutAtrium = true;

        parent::setUp();
    }

    protected function tearDown(): void
    {
        try {
            parent::tearDown();
        } finally {
            RosterServiceProvider::$withoutAtrium = false;
        }
    }

    /**
     * @return array<int, class-string>
     */
    protected function getPackageProviders($app): array
    {
        return [
            McpServiceProvider::class,
            RosterServiceProvider::class,
        ];
    }
}
