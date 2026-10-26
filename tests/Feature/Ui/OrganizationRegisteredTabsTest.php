<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Roster\Atrium\TabDefinition;
use RefactorCircus\Roster\Atrium\Tabs;
use RefactorCircus\Roster\Domains\Organization\Events\OrganizationShownActionEvent;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->admin = user(['name' => 'Admin']);
    $this->actingAs($this->admin);
    $this->organization = organization($this->admin, ['name' => 'Acme']);
    $this->calls = (object) ['resolve' => 0, 'authorize' => 0];

    // A host's tab view and component, written where only these tests look.
    $directory = sys_get_temp_dir().'/roster-registered-tabs-'.getmypid();

    if (! is_dir($directory.'/components')) {
        mkdir($directory.'/components', 0777, true);
    }

    file_put_contents($directory.'/orders.blade.php', <<<'BLADE'
        <div data-testid="host-view">
            <span data-testid="host-record">{{ $organization instanceof \RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel ? $organization->slug : 'replaced' }}</span>
            <span data-testid="host-tab">{{ is_string($tab) ? $tab : 'replaced' }}</span>
            <span data-testid="host-tabs">{{ is_array($tabs) ? implode(',', $tabs) : 'replaced' }}</span>
            <span data-testid="host-definition">{{ $registeredTab instanceof \RefactorCircus\Roster\Atrium\TabDefinition ? $registeredTab->key : 'replaced' }}</span>
            <span data-testid="host-errors">{{ $errors instanceof \Illuminate\Support\ViewErrorBag ? $errors->first('name') : 'replaced' }}</span>
            <span data-testid="host-total">{{ $total ?? 'none' }}</span>
        </div>
        BLADE);

    file_put_contents($directory.'/components/orders.blade.php', <<<'BLADE'
        @props(['organization', 'data' => []])
        <div data-testid="host-component">{{ $organization->slug }}|{{ implode(',', array_keys($data)) }}|{{ $data['slot'] ?? '' }}</div>
        BLADE);

    View::addNamespace('tabfixtures', $directory);
    Blade::anonymousComponentPath($directory.'/components', 'tabfixtures');
});

/**
 * A registered organization tab rendering the fixture view, its resolver
 * and authorize closures counted on the test's `$calls`.
 */
function countedOrganizationTab(object $calls, string $key = 'acme.orders'): TabDefinition
{
    return TabDefinition::make($key)
        ->label('Orders')
        ->icon('shopping-cart')
        ->view('tabfixtures::orders')
        ->resolve(function (Model $record) use ($calls): array {
            $calls->resolve++;

            return ['total' => 42];
        });
}

/**
 * A closure that counts its runs and answers `$answer`.
 *
 * @return Closure(Request, Model): bool
 */
function countedAuthorize(object $calls, bool $answer): Closure
{
    return function (Request $request, Model $record) use ($calls, $answer): bool {
        $calls->authorize++;

        return $answer;
    };
}

/**
 * Turn authorization on and sign in a user holding these permissions in
 * the organization.
 *
 * @param  array<int, string>  $permissions
 */
function organizationViewer(OrganizationModel $organization, array $permissions): User
{
    config()->set('roster.authorization', true);
    $viewer = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith($permissions, 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => $organization->getKey(),
    ]);

    return $viewer;
}

/**
 * The inner markup of a tab bar link: its icon.
 */
function organizationTabIcon(TestResponse $response, string $key): string
{
    preg_match('/<a [^>]*data-testid="tab-'.preg_quote($key, '/').'"[^>]*>(.*?)<\/a>/s', (string) $response->getContent(), $match);

    return trim($match[1] ?? '');
}

it('shows visible registered tabs after the built-in ones, in registration order', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('a')->view('tabfixtures::orders')->public())
        ->organization(TabDefinition::make('b')->view('tabfixtures::orders'))
        ->organization(TabDefinition::make('c')->view('tabfixtures::orders')->public());

    $this->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertSeeInOrder(['data-testid="tab-settings"', 'data-testid="tab-a"', 'data-testid="tab-c"'], false)
        ->assertDontSee('data-testid="tab-b"', false)
        ->assertSee('href="'.route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'a']).'"', false);
});

it('escapes a registered tab\'s label', function (): void {
    app(Tabs::class)->organization(TabDefinition::make('acme.orders')->label('<em>Orders</em>')->view('tabfixtures::orders')->public());

    $this->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertSee('&lt;em&gt;Orders&lt;/em&gt;', false)
        ->assertDontSee('<em>Orders</em>', false);
});

it('falls back to squares-2x2 for an icon Atrium does not know', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('acme.unknown')->icon('no-such-icon')->view('tabfixtures::orders')->public())
        ->organization(TabDefinition::make('acme.squares')->icon('squares-2x2')->view('tabfixtures::orders')->public());

    $response = $this->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertSee('data-testid="tab-members"', false)
        ->assertSee('data-testid="tab-settings"', false)
        ->assertSee('data-testid="tab-acme.unknown"', false);

    expect(organizationTabIcon($response, 'acme.unknown'))->not->toBe('')
        ->toBe(organizationTabIcon($response, 'acme.squares'));
});

it('renders the open registered tab\'s view with the organization and the resolver\'s data', function (): void {
    app(Tabs::class)->organization(countedOrganizationTab($this->calls)->public());

    $response = $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertViewHas('tab', 'acme.orders')
        ->assertSee('<span data-testid="host-record">acme</span>', false)
        ->assertSee('<span data-testid="host-total">42</span>', false);

    preg_match('/<a [^>]*data-testid="tab-acme.orders"[^>]*>/', (string) $response->getContent(), $tag);

    expect($tag[0] ?? '')->toContain('aria-current="page"');
});

it('runs the resolver only for the open registered tab, after the access check', function (array $query, int $calls, int $status, ?string $tab): void {
    app(Tabs::class)
        ->organization(countedOrganizationTab($this->calls)->public())
        ->organization(countedOrganizationTab($this->calls, 'acme.hidden'));

    $response = $this->get(route('atrium.roster.organizations.show', [$this->organization, ...$query]))->assertStatus($status);

    if ($tab !== null) {
        $response->assertViewHas('tab', $tab);
    }

    expect($this->calls->resolve)->toBe($calls);
})->with([
    'the registered tab' => [['tab' => 'acme.orders'], 1, 200, 'acme.orders'],
    'a built-in tab' => [['tab' => 'members'], 0, 200, 'members'],
    'no tab' => [[], 0, 200, 'members'],
    'a hidden registered tab' => [['tab' => 'acme.hidden'], 0, 403, null],
    'an unknown key' => [['tab' => 'acme.nope'], 0, 200, 'members'],
    'a list' => [['tab' => ['x']], 0, 200, 'members'],
    'a map' => [['tab' => ['a' => 'b']], 0, 200, 'members'],
]);

it('gives Roster\'s variables precedence over the resolver\'s data', function (): void {
    app(Tabs::class)->organization(TabDefinition::make('acme.orders')->view('tabfixtures::orders')->public()->resolve(fn (Model $record): array => [
        'organization' => 'host',
        'tab' => 'host',
        'tabs' => 'host',
        'registeredTab' => 'host',
        'errors' => 'host',
        'total' => 7,
    ]));

    $this->withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag(['name' => 'Roster error']))])
        ->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertSee('<span data-testid="host-record">acme</span>', false)
        ->assertSee('<span data-testid="host-tab">acme.orders</span>', false)
        ->assertSee('<span data-testid="host-tabs">members,teams,invitations,roles,sso,scim,settings,acme.orders</span>', false)
        ->assertSee('<span data-testid="host-definition">acme.orders</span>', false)
        ->assertSee('<span data-testid="host-errors">Roster error</span>', false)
        ->assertSee('<span data-testid="host-total">7</span>', false)
        ->assertSee('data-testid="tab-members"', false);
});

it('hands a component the resolver\'s data as one data prop', function (): void {
    app(Tabs::class)->organization(TabDefinition::make('acme.orders')->component('tabfixtures::orders')->public()->resolve(fn (Model $record): array => [
        'slot' => 'host slot',
        'attributes' => 'host attributes',
        'data-key' => 'dashed',
    ]));

    $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertSee('<div data-testid="host-component">acme|slot,attributes,data-key|host slot</div>', false);
});

it('authorizes the screen before any host closure runs', function (): void {
    app(Tabs::class)->organization(countedOrganizationTab($this->calls)->authorize(countedAuthorize($this->calls, true)));

    $viewer = organizationViewer($this->organization, ['roster.members.view']);

    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', $this->organization))->assertForbidden();
    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))->assertForbidden();
    $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', ['missing', 'tab' => 'acme.orders']))->assertNotFound();

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
});

it('opens a built-in tab by default even when only a registered one is visible', function (): void {
    // A host policy that lets the viewer open the screen, then refuses the
    // settings tab: no built-in tab is left for this viewer.
    $policy = new class
    {
        public bool $refuse = false;

        public function view(?Model $user = null, mixed ...$arguments): bool
        {
            return ! $this->refuse;
        }
    };

    app()->instance($policy::class, $policy);
    Gate::policy(OrganizationModel::class, $policy::class);
    Event::listen(OrganizationShownActionEvent::class, function () use ($policy): void {
        $policy->refuse = true;
        app(Permissions::class)->flush();
    });

    app(Tabs::class)->organization(countedOrganizationTab($this->calls)->public());

    $this->actingAs(organizationViewer($this->organization, []))
        ->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertViewHas('tab', 'settings')
        ->assertSee('data-testid="tab-acme.orders"', false)
        ->assertDontSee('data-testid="tab-settings"', false)
        ->assertDontSee('data-testid="host-view"', false);

    expect($this->calls->resolve)->toBe(0);
});

it('offers a registered tab by its permission, closure or public()', function (string $case, bool $visible): void {
    $organization = $this->organization;
    $viewer = organizationViewer($organization, ['roster.organizations.view', 'roster.members.view']);
    $closure = fn (bool $answer): Closure => fn (Request $request, Model $record): bool => $record->is($organization) && $request->user()?->is($viewer) && $answer;

    $tab = TabDefinition::make('acme.orders')->view('tabfixtures::orders');

    match ($case) {
        'permission' => $tab->permission('roster.members.view'),
        'ungranted permission' => $tab->permission('roster.teams.view'),
        'permission nothing grants' => $tab->permission('acme.orders.view'),
        'closure' => $tab->authorize($closure(true)),
        'refusing closure' => $tab->authorize($closure(false)),
        'public' => $tab->public(),
        'neither' => $tab,
        'both pass' => $tab->permission('roster.members.view')->authorize($closure(true)),
        'closure fails' => $tab->permission('roster.members.view')->authorize($closure(false)),
        'permission fails' => $tab->permission('roster.teams.view')->authorize($closure(true)),
        'neither passes' => $tab->permission('roster.teams.view')->authorize($closure(false)),
    };

    app(Tabs::class)->organization($tab);

    $bar = $this->actingAs($viewer)->get(route('atrium.roster.organizations.show', $organization))->assertOk();
    $visible
        ? $bar->assertSee('data-testid="tab-acme.orders"', false)
        : $bar->assertDontSee('data-testid="tab-acme.orders"', false);

    app()->forgetScopedInstances();

    $this->actingAs($viewer)
        ->get(route('atrium.roster.organizations.show', [$organization, 'tab' => 'acme.orders']))
        ->assertStatus($visible ? 200 : 403);
})->with([
    'permission only' => ['permission', true],
    'an ungranted permission' => ['ungranted permission', false],
    'a permission nothing grants' => ['permission nothing grants', false],
    'closure only' => ['closure', true],
    'a refusing closure' => ['refusing closure', false],
    'public()' => ['public', true],
    'neither' => ['neither', false],
    'both pass' => ['both pass', true],
    'the closure fails' => ['closure fails', false],
    'the permission fails' => ['permission fails', false],
    'neither passes' => ['neither passes', false],
]);

it('hides a tab whose authorize closure throws, reporting it once', function (): void {
    Exceptions::fake();

    app(Tabs::class)->organization(countedOrganizationTab($this->calls)->authorize(function (Request $request, Model $record): bool {
        $this->calls->authorize++;

        throw new RuntimeException('authorize broke');
    }));

    $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))->assertForbidden();

    expect($this->calls->authorize)->toBe(1);
    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'authorize broke');

    app()->forgetScopedInstances();

    $this->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertViewHas('tab', 'members')
        ->assertDontSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(2)
        ->and($this->calls->resolve)->toBe(0);
    Exceptions::assertReportedCount(2);
});

it('runs no closure when no tab is registered on the organization screen', function (): void {
    app(Tabs::class)->user(countedOrganizationTab($this->calls)->authorize(countedAuthorize($this->calls, true)));

    $this->get(route('atrium.roster.organizations.show', $this->organization))->assertOk();
    $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))->assertOk()->assertViewHas('tab', 'members');

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
});

it('asks again on each request', function (): void {
    Exceptions::fake();
    $ada = user();
    $bob = user();

    app(Tabs::class)
        ->organization(countedOrganizationTab($this->calls)->authorize(function (Request $request, Model $record) use ($ada): bool {
            $this->calls->authorize++;

            return $request->user()?->is($ada) === true;
        }))
        ->organization(TabDefinition::make('acme.broken')->view('tabfixtures::orders')->authorize(function (): bool {
            throw new RuntimeException('authorize broke');
        }));

    $this->actingAs($ada)->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(1);
    Exceptions::assertReportedCount(1);

    app()->forgetScopedInstances();

    $this->actingAs($bob)->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertDontSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(2);
    Exceptions::assertReportedCount(2);
});

it('lets a throwing resolver propagate', function (): void {
    app(Tabs::class)->organization(TabDefinition::make('acme.orders')->view('tabfixtures::orders')->public()->resolve(function (Model $record): array {
        throw new RuntimeException('resolver broke');
    }));

    $this->withoutExceptionHandling();

    expect(fn () => $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders'])))
        ->toThrow(RuntimeException::class, 'resolver broke');
});

it('keeps all-digit keys as strings', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('123')->view('tabfixtures::orders')->public())
        ->organization(TabDefinition::make('007')->view('tabfixtures::orders')->public());

    $this->get(route('atrium.roster.organizations.show', $this->organization))
        ->assertOk()
        ->assertSeeInOrder(['data-testid="tab-settings"', 'data-testid="tab-123"', 'data-testid="tab-007"'], false)
        ->assertViewHas('tabs', fn (array $tabs): bool => array_slice($tabs, -2) === ['123', '007']);

    foreach (['123', '007'] as $key) {
        $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => $key]))
            ->assertOk()
            ->assertViewHas('tab', fn (mixed $tab): bool => $tab === $key)
            ->assertSee('<span data-testid="host-tab">'.$key.'</span>', false);
    }
});

it('renders the deleted page and ignores ?tab= on a deleted organization', function (): void {
    app(Tabs::class)->organization(countedOrganizationTab($this->calls)->authorize(countedAuthorize($this->calls, true)));
    $this->organization->delete();

    $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertViewIs('roster::ui.organizations.deleted')
        ->assertDontSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
});
