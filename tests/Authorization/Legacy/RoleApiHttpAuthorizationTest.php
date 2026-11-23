<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Support\Facades\Route;

it('covers every role and permission route', function (): void {
    $names = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => isRoleApiRoute($name))
        ->sort()
        ->values()
        ->all();

    $covered = collect(roleApiRoutes())->map(fn (array $route): string => $route[1])->sort()->values()->all();

    expect($covered)->toBe($names);
});

it('rejects guests with 401', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = roleApiWorld();

    $this->json($method, route($name, $parameters($world)), $body)->assertUnauthorized();
})->with(roleApiRoutes());

it('rejects users without the permission with 403', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = roleApiWorld();

    $this->actingAs(user())->json($method, route($name, $parameters($world)), $body)->assertForbidden();
})->with(roleApiRoutes());

it('lets a super-admin through', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = roleApiWorld();
    $admin = user();
    grant($admin, 'super-admin');

    $status = $this->actingAs($admin)->json($method, route($name, $parameters($world)), $body)->status();

    expect($status)->not->toBeIn([401, 403]);
})->with(roleApiRoutes());

it('lets users list their own permissions', function (): void {
    $world = roleApiWorld();
    $this->actingAs($world['target']);

    $this->getJson(route('roster.users.permissions', $world['target']->getRouteKey()))->assertOk();
    $this->getJson(route('roster.users.permissions', user()->getRouteKey()))->assertForbidden();
});
