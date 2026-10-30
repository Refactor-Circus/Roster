<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;

it('covers every roster API route', function (): void {
    $names = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_starts_with($name, 'roster.') && ! str_starts_with($name, 'roster.invitations.') && ! str_starts_with($name, 'roster.impersonation.') && $name !== 'roster.transfers.file' && $name !== 'roster.history.index')
        ->map(fn (string $name): string => substr($name, 7))
        ->sort()
        ->values()
        ->all();

    $covered = collect(apiRoutes())->map(fn (array $route): string => substr($route[1], 7))->sort()->values()->all();

    expect($covered)->toBe($names);
});

it('rejects guests with 401', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = authorizationWorld();

    $this->json($method, route($name, $parameters($world)), $body)->assertUnauthorized();
})->with(apiRoutes());

it('rejects users without the permission with 403', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = authorizationWorld();

    $this->actingAs(user())->json($method, route($name, $parameters($world)), $body)->assertForbidden();
})->with(fn (): array => array_diff_key(apiRoutes(), array_flip(OPEN_TO_SIGNED_IN)));

it('opens import templates to any signed-in user', function (): void {
    $this->actingAs(user())->get(route('roster.imports.templates.show', 'import_members'))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8')
        ->assertSee('email,name,display_name,teams,role');
});

it('lets a super-admin through', function (string $method, string $name, Closure $parameters, array $body): void {
    $world = authorizationWorld();
    $admin = user();
    grant($admin, 'super-admin');

    $status = $this->actingAs($admin)->json($method, route($name, $parameters($world)), $body)->status();

    expect($status)->not->toBeIn([401, 403]);
})->with(apiRoutes());

it('lets users view themselves and edit their own profile and context', function (): void {
    $world = authorizationWorld();
    $target = $world['target'];
    $this->actingAs($target);

    $this->getJson(route('roster.users.show', $target->getRouteKey()))->assertOk();
    $this->patchJson(route('roster.users.profile.update', $target->getRouteKey()), ['bio' => 'Me'])->assertOk();
    $this->putJson(route('roster.users.context.update', $target->getRouteKey()), ['organization' => 'acme'])->assertOk();
    $this->getJson(route('roster.users.permissions', $target->getRouteKey()))->assertOk();

    // But not other account fields, nor anyone else.
    $this->patchJson(route('roster.users.update', $target->getRouteKey()), ['name' => 'x'])->assertForbidden();
    $this->getJson(route('roster.users.show', user()->getRouteKey()))->assertForbidden();
});

it('scopes organization permissions to that organization', function (): void {
    authorizationWorld();
    $other = organization(attributes: ['name' => 'Globex']);
    $admin = user();
    app(AddMemberAction::class)->execute($other, new AddMemberData(user: $admin->getRouteKey()));
    grant($admin, 'admin', $other);

    $this->actingAs($admin);

    $this->getJson(route('roster.organizations.members.index', 'globex'))->assertOk();
    $this->getJson(route('roster.organizations.members.index', 'acme'))->assertForbidden();
});

it('lets a team lead manage their team only', function (): void {
    $world = authorizationWorld();
    $lead = $world['target'];
    grant($lead, 'lead', team: $world['ops']);
    $this->actingAs($lead);

    $this->patchJson(route('roster.organizations.teams.update', ['acme', 'ops']), ['name' => 'Ops 2'])->assertOk();
    $this->postJson(route('roster.organizations.teams.store', 'acme'), ['name' => 'Other'])->assertForbidden();
});

it('needs roster.audit.view for the shared history route', function (): void {
    // Served by refactor-circus/keystone, which answers guests 401 like Roster's own routes.
    $this->getJson(route('roster.history.index'))->assertUnauthorized();

    $this->actingAs($viewer = user())->getJson(route('roster.history.index'))->assertForbidden();

    grant($viewer, 'super-admin');

    $this->getJson(route('roster.history.index'))->assertNotFound();
});
