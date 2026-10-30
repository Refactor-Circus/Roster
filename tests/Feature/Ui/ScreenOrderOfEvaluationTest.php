<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

beforeEach(function (): void {
    $this->calls = (object) ['authorize' => 0, 'resolve' => 0];

    // A host's tab and section view, written where only these tests look.
    $directory = sys_get_temp_dir().'/roster-screen-order-'.getmypid();

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory.'/extension.blade.php', '<p>{{ $screen->key }}</p>');
    View::addNamespace('screenorder', $directory);
});

/**
 * Register a tab and an `aside` section on the screen whose `authorize` and
 * resolver closures count their runs on `$calls`.
 */
function countingScreenExtensions(string $key, object $calls): void
{
    $authorize = function (Request $request, ?Model $record) use ($calls): bool {
        $calls->authorize++;

        return true;
    };

    $resolve = function (?Model $record) use ($calls): array {
        $calls->resolve++;

        return [];
    };

    Atrium::screens()
        ->tab($key, TabDefinition::make('test.tab')->view('screenorder::extension')->authorize($authorize)->resolve($resolve))
        ->section($key, 'aside', SectionDefinition::make('test.aside')->view('screenorder::extension')->authorize($authorize)->resolve($resolve));
}

/**
 * Every detail screen Roster declares, with its route and route parameters
 * for a record nobody in the test may open.
 *
 * @return array<string, array{string, Closure(): array<int|string, mixed>}>
 */
function detailScreens(): array
{
    return [
        'roster.user-create' => ['roster.user-create', fn (): array => ['atrium.roster.users.create', []]],
        'roster.user' => ['roster.user', fn (): array => ['atrium.roster.users.show', [user()->getRouteKey()]]],
        'roster.organization-create' => ['roster.organization-create', fn (): array => ['atrium.roster.organizations.create', []]],
        'roster.organization' => ['roster.organization', fn (): array => ['atrium.roster.organizations.show', [organization()]]],
        'roster.team' => ['roster.team', function (): array {
            $organization = organization();
            $team = TeamModel::factory()->create(['organization_id' => $organization->getKey()]);

            return ['atrium.roster.teams.show', [$organization, $team->slug]];
        }],
        'roster.role' => ['roster.role', function (): array {
            $role = RoleModel::factory()->create(['scope' => 'organization', 'organization_id' => organization()->getKey()]);

            return ['atrium.roster.roles.show', [$role->id]];
        }],
        'roster.transfer' => ['roster.transfer', function (): array {
            $transfer = TransferModel::factory()->create(['requested_by' => user()->getKey(), 'organization_id' => organization()->getKey()]);

            return ['atrium.roster.transfers.show', [$transfer->id]];
        }],
    ];
}

it('runs no host closure for a viewer the screen refuses', function (string $key, Closure $record): void {
    [$route, $parameters] = $record();

    countingScreenExtensions($key, $this->calls);

    // Authorization on, and a viewer holding nothing.
    config()->set('roster.authorization', true);
    $this->actingAs(user());

    $this->get(route($route, $parameters))->assertForbidden();

    app()->forgetScopedInstances();

    $this->get(route($route, [...$parameters, 'tab' => 'test.tab']))->assertForbidden();

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
})->with(detailScreens());

it('runs every host closure for a viewer the screen admits', function (string $key, Closure $record): void {
    [$route, $parameters] = $record();

    countingScreenExtensions($key, $this->calls);
    $this->actingAs(user());

    // The control: the same closures do run once the screen admits the viewer.
    $this->get(route($route, [...$parameters, 'tab' => 'test.tab']))->assertOk();

    expect($this->calls->authorize)->toBeGreaterThan(0)
        ->and($this->calls->resolve)->toBeGreaterThan(0);
})->with(detailScreens());

it('runs no host closure on a deleted record\'s page', function (string $key, Closure $deleted, string $route): void {
    $this->actingAs(user());
    countingScreenExtensions($key, $this->calls);

    $this->get(route($route, [$deleted(), 'tab' => 'test.tab']))
        ->assertOk()
        ->assertDontSee('data-testid="tab-', false)
        ->assertDontSee('data-testid="section-', false);

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
})->with([
    'a deleted organization' => ['roster.organization', function (): OrganizationModel {
        $organization = organization();
        $organization->delete();

        return $organization;
    }, 'atrium.roster.organizations.show'],
    'a deleted user' => ['roster.user', function (): string {
        $user = user();
        $user->delete();

        return (string) $user->getRouteKey();
    }, 'atrium.roster.users.show'],
]);
