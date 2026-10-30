<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Atrium\Domains\Navigation\Data\NavItem;
use RefactorCircus\Roster\Atrium\RosterPlugin;

/*
 * Other packages' UI left Roster's screens: Cortex registers its own
 * redirect-domains UI and Keen its own history, so Roster declares neither.
 */

it('no longer registers the redirect-domain routes', function (string $name): void {
    expect(Route::has($name))->toBeFalse();
})->with([
    'atrium.roster.organizations.redirect-domains.store',
    'atrium.roster.organizations.redirect-domains.destroy',
    'atrium.roster.users.redirect-domains.store',
    'atrium.roster.users.redirect-domains.destroy',
]);

it('drops the translations only the removed views used', function (): void {
    $translations = require dirname(__DIR__, 3).'/lang/en/roster.php';

    foreach (['mcp', 'redirect_domains', 'redirect_domains_hint', 'no_redirect_domains'] as $key) {
        expect($translations)->not->toHaveKey($key);
    }
});

it('links no history from its sidebar group', function (): void {
    $routes = [];
    $collect = function (array $items) use (&$collect, &$routes): void {
        foreach ($items as $item) {
            /** @var NavItem $item */
            $routes[] = (string) $item->route;
            $collect($item->children);
        }
    };

    $collect(app(RosterPlugin::class)->navigation());

    expect(array_filter($routes, fn (string $route): bool => str_starts_with($route, 'atrium.history')))->toBeEmpty();
});

it('opens the default organization tab for the removed mcp tab', function (): void {
    $this->actingAs($ada = user(['name' => 'Ada']));
    $acme = organization($ada);

    $this->get(route('atrium.roster.organizations.show', [$acme, 'tab' => 'mcp']))
        ->assertOk()
        ->assertDontSee('data-testid="tab-mcp"', false)
        ->assertDontSee('data-testid="redirect-domains-card"', false);
});
