<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Route;

/**
 * Roster v2 ships no dashboard screens: Atrium v2 builds them on the JSON API.
 */
it('registers no atrium.roster.* route', function (): void {
    $names = collect(Route::getRoutes()->getRoutes())
        ->map(fn (RegisteredRoute $route): string => (string) $route->getName())
        ->filter(fn (string $name): bool => str_starts_with($name, 'atrium.roster.'))
        ->values()
        ->all();

    // The positive control: Roster's API routes are mounted.
    expect(Route::has('roster.users.index'))->toBeTrue()
        ->and($names)->toBe([]);
});

it('has no :dataset class', function (string $class): void {
    expect(class_exists($class))->toBeFalse();
})->with([
    'RefactorCircus\Roster\Atrium\RosterPlugin',
    'RefactorCircus\Roster\Atrium\ScreenAccess',
    'RefactorCircus\Roster\Atrium\Badges',
    'RefactorCircus\Roster\Atrium\Features\RosterSupportFeature',
    'RefactorCircus\Roster\Atrium\Http\Controllers\UserUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\OrganizationUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\TeamUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\InvitationUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\RoleUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\PermissionUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\ImpersonationUiController',
    'RefactorCircus\Roster\Atrium\Http\Controllers\TransferUiController',
]);

it('has no roster.atrium config', function (): void {
    $config = require dirname(__DIR__, 2).'/config/roster.php';

    expect(config('roster.atrium'))->toBeNull()
        ->and($config)->toHaveKey('routes')->not->toHaveKey('atrium');
});
