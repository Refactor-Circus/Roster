<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Atrium\Domains\Screens\Data\SectionDefinition;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Atrium\Facades\Atrium;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->admin = user(['name' => 'Admin']);
    $this->actingAs($this->admin);

    // A host's tab and section views, written where only these tests look.
    $directory = sys_get_temp_dir().'/roster-screen-declarations-'.getmypid();

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory.'/tab.blade.php', '<p data-testid="declared-tab">{{ $screen->key }}</p>');
    file_put_contents($directory.'/section.blade.php', '<span>{{ $screen->key }}</span>');
    View::addNamespace('declaredscreens', $directory);
});

/**
 * Every screen Roster declares, with its kind.
 *
 * @return array<string, array{string, string}>
 */
function declaredScreens(): array
{
    return [
        'roster.users' => ['roster.users', 'list'],
        'roster.user-create' => ['roster.user-create', 'detail'],
        'roster.user' => ['roster.user', 'detail'],
        'roster.organizations' => ['roster.organizations', 'list'],
        'roster.organization-create' => ['roster.organization-create', 'detail'],
        'roster.organization' => ['roster.organization', 'detail'],
        'roster.team' => ['roster.team', 'detail'],
        'roster.roles' => ['roster.roles', 'list'],
        'roster.role' => ['roster.role', 'detail'],
        'roster.permissions' => ['roster.permissions', 'list'],
        'roster.impersonations' => ['roster.impersonations', 'list'],
        'roster.transfers' => ['roster.transfers', 'list'],
        'roster.transfer' => ['roster.transfer', 'detail'],
    ];
}

/**
 * Seed a record for every screen and return the screen's route, its route
 * parameters and, on a list screen, the view variable holding its rows.
 *
 * @return array{string, array<int|string, mixed>, ?string}
 */
function declaredScreenRoute(string $key, User $admin): array
{
    $organization = organization($admin, ['name' => 'Acme']);
    $team = TeamModel::factory()->create(['organization_id' => $organization->getKey()]);
    $role = RoleModel::factory()->create(['scope' => 'organization', 'organization_id' => $organization->getKey()]);
    ImpersonationModel::factory()->create(['impersonator_id' => $admin->getKey(), 'user_id' => user()->getKey(), 'organization_id' => $organization->getKey()]);
    $transfer = TransferModel::factory()->create(['requested_by' => $admin->getKey(), 'organization_id' => $organization->getKey()]);

    return match ($key) {
        'roster.users' => ['atrium.roster.users.index', [], 'users'],
        'roster.user-create' => ['atrium.roster.users.create', [], null],
        'roster.user' => ['atrium.roster.users.show', [$admin->getRouteKey()], null],
        'roster.organizations' => ['atrium.roster.organizations.index', [], 'organizations'],
        'roster.organization-create' => ['atrium.roster.organizations.create', [], null],
        'roster.organization' => ['atrium.roster.organizations.show', [$organization], null],
        'roster.team' => ['atrium.roster.teams.show', [$organization, $team->slug], null],
        'roster.roles' => ['atrium.roster.roles.index', [], 'roles'],
        'roster.role' => ['atrium.roster.roles.show', [$role->id], null],
        'roster.permissions' => ['atrium.roster.permissions.index', [], 'permissions'],
        'roster.impersonations' => ['atrium.roster.impersonations.index', [], 'impersonations'],
        'roster.transfers' => ['atrium.roster.transfers.index', [], 'transfers'],
        'roster.transfer' => ['atrium.roster.transfers.show', [$transfer->id], null],
    };
}

/**
 * How many elements carry this exact `data-testid`.
 */
function declaredTestIdCount(TestResponse $response, string $testId): int
{
    return substr_count((string) $response->getContent(), 'data-testid="'.$testId.'"');
}

it('declares exactly the standard slots of its kind, each rendering its sections', function (string $key, string $kind): void {
    [$route, $parameters, $rows] = declaredScreenRoute($key, $this->admin);

    $slots = $kind === 'detail' ? ['header.actions', 'before', 'after', 'aside'] : ['header.actions', 'before', 'after', 'row.actions'];
    $undeclared = $kind === 'detail' ? ['row.actions', 'footer'] : ['aside', 'footer'];

    Atrium::screens()->tab($key, TabDefinition::make('test.tab')->label('Test')->view('declaredscreens::tab')->public());

    foreach ([...$slots, ...$undeclared] as $slot) {
        Atrium::screens()->section($key, $slot, SectionDefinition::make('test.'.$slot)->view('declaredscreens::section')->public());
    }

    $response = $this->get(route($route, $parameters))
        ->assertOk()
        ->assertSee('data-testid="tab-test.tab"', false)
        ->assertDontSee('data-testid="tab-panel-test.tab"', false);

    foreach (array_diff($slots, ['row.actions']) as $slot) {
        expect(declaredTestIdCount($response, 'section-test.'.$slot))->toBe(1, "{$key} renders its {$slot} section once");
    }

    foreach ($undeclared as $slot) {
        $response->assertDontSee('data-testid="section-test.'.$slot.'"', false);
    }

    if ($rows !== null) {
        $count = $response->viewData($rows)->count();

        expect($count)->toBeGreaterThan(0)
            ->and(declaredTestIdCount($response, 'section-test.row.actions'))->toBe($count);
    }

    app()->forgetScopedInstances();

    // An open registered tab replaces the page: only header actions stay.
    $panel = $this->get(route($route, [...$parameters, 'tab' => 'test.tab']))
        ->assertOk()
        ->assertSee('data-testid="tab-panel-test.tab"', false)
        ->assertSee('<p data-testid="declared-tab">'.$key.'</p>', false);

    expect(declaredTestIdCount($panel, 'section-test.header.actions'))->toBe(1)
        ->and(substr_count((string) $panel->getContent(), 'data-testid="section-'))->toBe(1);
})->with(declaredScreens());

it('renders no extension markup while nothing is registered', function (string $key, string $kind): void {
    [$route, $parameters] = declaredScreenRoute($key, $this->admin);

    $response = $this->get(route($route, $parameters))
        ->assertOk()
        ->assertDontSee('data-testid="section-', false)
        ->assertDontSee('data-testid="tab-test.', false);

    if ($key === 'roster.organization') {
        // Its built-in tabs always make a bar: exactly the ones the viewer may open.
        preg_match_all('/data-testid="tab-([^"]+)"/', (string) $response->getContent(), $tabs);

        $response->assertSee('data-testid="screen-tabs"', false);
        expect($tabs[1])->toBe(['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'settings']);
    } else {
        $response->assertDontSee('data-testid="screen-tabs"', false)
            ->assertDontSee('data-testid="tab-', false);
    }
})->with(declaredScreens());

it('declares the screens on the organization it shows', function (): void {
    $organization = organization($this->admin, ['name' => 'Acme']);

    Atrium::screens()->section('roster.organization', 'aside', SectionDefinition::make('test.record')
        ->view('declaredscreens::section')
        ->authorize(fn (Request $request, ?Model $record): bool => $record instanceof OrganizationModel && $record->is($organization)));

    $this->get(route('atrium.roster.organizations.show', $organization))
        ->assertOk()
        ->assertSee('data-testid="screen-aside"', false)
        ->assertSee('data-testid="section-test.record"', false);
});
