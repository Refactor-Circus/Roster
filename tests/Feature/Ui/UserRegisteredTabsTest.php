<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Roster\Atrium\TabDefinition;
use RefactorCircus\Roster\Atrium\Tabs;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Support\Tables;
use Workbench\App\Models\User;

beforeEach(function (): void {
    $this->admin = user(['name' => 'Admin']);
    $this->actingAs($this->admin);
    $this->target = user(['name' => 'Ada', 'email' => 'ada@example.com']);
    $this->calls = (object) ['resolve' => 0, 'authorize' => 0];

    // A host's tab view and component, written where only these tests look.
    $directory = sys_get_temp_dir().'/roster-user-tabs-'.getmypid();

    if (! is_dir($directory.'/components')) {
        mkdir($directory.'/components', 0777, true);
    }

    file_put_contents($directory.'/orders.blade.php', <<<'BLADE'
        <div data-testid="host-view">
            <span data-testid="host-record">{{ $user instanceof \Illuminate\Database\Eloquent\Model ? $user->getAttribute('email') : 'replaced' }}</span>
            <span data-testid="host-tab">{{ is_string($tab) ? $tab : 'replaced' }}</span>
            <span data-testid="host-tabs">{{ is_array($tabs) ? implode(',', $tabs) : 'replaced' }}</span>
            <span data-testid="host-definition">{{ $registeredTab instanceof \RefactorCircus\Roster\Atrium\TabDefinition ? $registeredTab->key : 'replaced' }}</span>
            <span data-testid="host-errors">{{ $errors instanceof \Illuminate\Support\ViewErrorBag ? $errors->first('name') : 'replaced' }}</span>
            <span data-testid="host-total">{{ $total ?? 'none' }}</span>
        </div>
        BLADE);

    file_put_contents($directory.'/components/orders.blade.php', <<<'BLADE'
        @props(['user', 'data' => []])
        <div data-testid="host-component">{{ $user->getAttribute('email') }}|{{ implode(',', array_keys($data)) }}|{{ $data['slot'] ?? '' }}</div>
        BLADE);

    View::addNamespace('usertabs', $directory);
    Blade::anonymousComponentPath($directory.'/components', 'usertabs');
});

/**
 * A registered user tab rendering the fixture view, its resolver counted on
 * the test's `$calls`.
 */
function countedUserTab(object $calls, string $key = 'acme.orders'): TabDefinition
{
    return TabDefinition::make($key)
        ->label('Orders')
        ->icon('shopping-cart')
        ->view('usertabs::orders')
        ->resolve(function (Model $record) use ($calls): array {
            $calls->resolve++;

            return ['total' => 42];
        });
}

/**
 * An authorize closure that counts its runs and answers `$answer`.
 *
 * @return Closure(Request, Model): bool
 */
function countedUserAuthorize(object $calls, bool $answer): Closure
{
    return function (Request $request, Model $record) use ($calls, $answer): bool {
        $calls->authorize++;

        return $answer;
    };
}

/**
 * Turn authorization on and make a user holding these permissions globally.
 *
 * @param  array<int, string>  $permissions
 */
function userScreenViewer(array $permissions): User
{
    config()->set('roster.authorization', true);
    $viewer = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith($permissions)->id,
        'user_id' => $viewer->getKey(),
    ]);

    return $viewer;
}

/**
 * The user page's markup without its tab bar, CSRF tokens or layout
 * whitespace, to compare one rendering of the page with another.
 */
function userPageWithoutTabBar(TestResponse $response): string
{
    $content = (string) $response->getContent();
    $content = (string) preg_replace('/<div class="mt-5 flex flex-col gap-4">\s*<nav .*?<\/nav>\s*<\/div>/s', '', $content);
    $content = (string) preg_replace('/(name="_token" value="|name="csrf-token" content=")[^"]*"/', '$1"', $content);

    return trim((string) preg_replace(['/>\s+</', '/\s+/'], ['><', ' '], $content));
}

/**
 * The tab bar links carrying `aria-current="page"`.
 *
 * @return list<string>
 */
function currentUserTabs(TestResponse $response): array
{
    preg_match_all('/<a [^>]*data-testid="tab-[^"]+"[^>]*>/', (string) $response->getContent(), $tags);

    return array_values(array_filter($tags[0], fn (string $tag): bool => str_contains($tag, 'aria-current="page"')));
}

it('leaves the page unchanged while no user tab is visible', function (Closure $register, array $query): void {
    $show = fn (array $query): TestResponse => $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), ...$query]));

    $unchanged = userPageWithoutTabBar($show([])->assertOk());

    $register($this->calls);
    app()->forgetScopedInstances();

    $response = $show($query)
        ->assertOk()
        ->assertViewHas('tab', 'overview')
        ->assertViewHas('tabs', [])
        ->assertDontSee('data-testid="tab-', false)
        ->assertSee('data-testid="danger-zone"', false);

    expect(userPageWithoutTabBar($response))->toBe($unchanged)
        ->and($this->calls->resolve)->toBe(0);
})->with([
    // Typed Closure parameters get the dataset's closure itself, uncalled.
    'no tab registered' => [function (object $calls): void {}],
    'a tab with neither check' => [function (object $calls): void {
        app(Tabs::class)->user(countedUserTab($calls));
    }],
    'a tab its closure refuses' => [function (object $calls): void {
        app(Tabs::class)->user(countedUserTab($calls)->authorize(countedUserAuthorize($calls, false)));
    }],
    'a tab on the organization screen only' => [function (object $calls): void {
        app(Tabs::class)->organization(countedUserTab($calls)->public());
    }],
])->with([
    'no ?tab=' => [[]],
    '?tab=overview' => [['tab' => 'overview']],
    'an empty ?tab=' => [['tab' => '']],
    'an unknown key' => [['tab' => 'acme.nope']],
    '?tab[]=x' => [['tab' => ['x']]],
]);

it('refuses a hidden registered tab even while no user tab is visible', function (Closure $register): void {
    $register($this->calls);

    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))->assertForbidden();

    expect($this->calls->resolve)->toBe(0);
})->with([
    'a tab with neither check' => [function (object $calls): void {
        app(Tabs::class)->user(countedUserTab($calls));
    }],
    'a tab its closure refuses' => [function (object $calls): void {
        app(Tabs::class)->user(countedUserTab($calls)->authorize(countedUserAuthorize($calls, false)));
    }],
]);

it('opens the overview by default, holding all of the page\'s content', function (): void {
    $unchanged = userPageWithoutTabBar($this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))->assertOk());

    app(Tabs::class)->user(countedUserTab($this->calls)->public());
    app()->forgetScopedInstances();

    $response = $this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))
        ->assertOk()
        ->assertViewHas('tab', 'overview')
        ->assertViewHas('tabs', ['overview', 'acme.orders'])
        ->assertSeeInOrder(['data-testid="tab-overview"', 'data-testid="tab-acme.orders"', 'data-testid="save-account"'], false)
        ->assertSee('href="'.route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'overview']).'"', false)
        ->assertSee('href="'.route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']).'"', false)
        ->assertSee('data-testid="save-profile"', false)
        ->assertSee('data-testid="status-form"', false)
        ->assertSee('data-testid="roles-card"', false)
        ->assertSee('data-testid="impersonate-card"', false)
        ->assertSee('data-testid="danger-zone"', false)
        ->assertDontSee('data-testid="host-view"', false);

    expect(currentUserTabs($response))->toHaveCount(1)
        ->and(currentUserTabs($response)[0])->toContain('data-testid="tab-overview"')
        ->and(userPageWithoutTabBar($response))->toBe($unchanged)
        ->and($this->calls->resolve)->toBe(0);

    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'overview']))
        ->assertOk()
        ->assertViewHas('tab', 'overview')
        ->assertSee('data-testid="danger-zone"', false);
});

it('renders the open registered tab\'s view with the user and the resolver\'s data', function (): void {
    app(Tabs::class)->user(countedUserTab($this->calls)->public());

    $response = $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertViewHas('tab', 'acme.orders')
        ->assertSee('Ada')
        ->assertSee('<span data-testid="host-record">ada@example.com</span>', false)
        ->assertSee('<span data-testid="host-total">42</span>', false)
        ->assertSee('data-testid="tab-overview"', false)
        ->assertDontSee('data-testid="danger-zone"', false)
        ->assertDontSee('data-testid="save-account"', false);

    expect(currentUserTabs($response))->toHaveCount(1)
        ->and(currentUserTabs($response)[0])->toContain('data-testid="tab-acme.orders"')
        ->and($this->calls->resolve)->toBe(1);
});

it('runs the resolver only for the open registered tab, after the access check', function (array $query, int $calls, int $status, ?string $tab): void {
    app(Tabs::class)
        ->user(countedUserTab($this->calls)->public())
        ->user(countedUserTab($this->calls, 'acme.hidden'));

    $response = $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), ...$query]))->assertStatus($status);

    if ($tab !== null) {
        $response->assertViewHas('tab', $tab);
    }

    expect($this->calls->resolve)->toBe($calls);
})->with([
    'the registered tab' => [['tab' => 'acme.orders'], 1, 200, 'acme.orders'],
    'the overview' => [['tab' => 'overview'], 0, 200, 'overview'],
    'no tab' => [[], 0, 200, 'overview'],
    'an empty ?tab=' => [['tab' => ''], 0, 200, 'overview'],
    'a hidden registered tab' => [['tab' => 'acme.hidden'], 0, 403, null],
    'an unknown key' => [['tab' => 'acme.nope'], 0, 200, 'overview'],
    'a list' => [['tab' => ['x']], 0, 200, 'overview'],
    'a map' => [['tab' => ['a' => 'b']], 0, 200, 'overview'],
]);

it('gives Roster\'s variables precedence over the resolver\'s data', function (): void {
    app(Tabs::class)->user(TabDefinition::make('acme.orders')->view('usertabs::orders')->public()->resolve(fn (Model $record): array => [
        'user' => 'host',
        'tab' => 'host',
        'tabs' => 'host',
        'registeredTab' => 'host',
        'errors' => 'host',
        'total' => 7,
    ]));

    $this->withSession(['errors' => (new ViewErrorBag)->put('default', new MessageBag(['name' => 'Roster error']))])
        ->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertSee('<span data-testid="host-record">ada@example.com</span>', false)
        ->assertSee('<span data-testid="host-tab">acme.orders</span>', false)
        ->assertSee('<span data-testid="host-tabs">overview,acme.orders</span>', false)
        ->assertSee('<span data-testid="host-definition">acme.orders</span>', false)
        ->assertSee('<span data-testid="host-errors">Roster error</span>', false)
        ->assertSee('<span data-testid="host-total">7</span>', false)
        ->assertSee('data-testid="tab-overview"', false);
});

it('hands a component the user and the resolver\'s data as one data prop', function (): void {
    app(Tabs::class)->user(TabDefinition::make('acme.orders')->component('usertabs::orders')->public()->resolve(fn (Model $record): array => [
        'slot' => 'host slot',
        'attributes' => 'host attributes',
        'data-key' => 'dashed',
    ]));

    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertSee('<div data-testid="host-component">ada@example.com|slot,attributes,data-key|host slot</div>', false);
});

it('authorizes the screen before any host closure runs', function (): void {
    app(Tabs::class)->user(countedUserTab($this->calls)->authorize(countedUserAuthorize($this->calls, true)));

    $viewer = userScreenViewer(['roster.users.update']);

    $this->actingAs($viewer)->get(route('atrium.roster.users.show', $this->target->getRouteKey()))->assertForbidden();
    $this->actingAs($viewer)->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))->assertForbidden();
    $this->actingAs($viewer)->get(route('atrium.roster.users.show', ['missing', 'tab' => 'acme.orders']))->assertNotFound();

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
});

it('offers a registered tab by its permission, closure or public()', function (string $case, bool $visible): void {
    $viewer = userScreenViewer(['roster.users.view', 'roster.users.update']);
    $target = $case === 'own page' ? $viewer : $this->target;
    $closure = fn (bool $answer): Closure => fn (Request $request, Model $record): bool => $record->is($target) && $request->user()?->is($viewer) && $answer;

    // Held only in an organization: the user screen asks globally.
    $organization = organization();
    RoleAssignmentModel::query()->create([
        'role_id' => roleWith(['roster.roles.view'], 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => $organization->getKey(),
    ]);

    $tab = TabDefinition::make('acme.orders')->view('usertabs::orders');

    match ($case) {
        'permission' => $tab->permission('roster.users.update'),
        'ungranted permission' => $tab->permission('roster.users.delete'),
        'permission nothing grants' => $tab->permission('acme.orders.view'),
        'permission held in an organization' => $tab->permission('roster.roles.view'),
        'own page' => $tab->permission('roster.users.delete'),
        'closure' => $tab->authorize($closure(true)),
        'refusing closure' => $tab->authorize($closure(false)),
        'public' => $tab->public(),
        'neither' => $tab,
        'both pass' => $tab->permission('roster.users.update')->authorize($closure(true)),
        'closure fails' => $tab->permission('roster.users.update')->authorize($closure(false)),
        'permission fails' => $tab->permission('roster.users.delete')->authorize($closure(true)),
        'neither passes' => $tab->permission('roster.users.delete')->authorize($closure(false)),
    };

    app(Tabs::class)->user($tab);

    $bar = $this->actingAs($viewer)->get(route('atrium.roster.users.show', $target->getRouteKey()))->assertOk();
    $visible
        ? $bar->assertSee('data-testid="tab-acme.orders"', false)->assertSee('data-testid="tab-overview"', false)
        : $bar->assertDontSee('data-testid="tab-', false);

    app()->forgetScopedInstances();

    $this->actingAs($viewer)
        ->get(route('atrium.roster.users.show', [$target->getRouteKey(), 'tab' => 'acme.orders']))
        ->assertStatus($visible ? 200 : 403);
})->with([
    'permission only' => ['permission', true],
    'an ungranted permission' => ['ungranted permission', false],
    'a permission nothing grants' => ['permission nothing grants', false],
    'a permission held only in an organization' => ['permission held in an organization', false],
    'a permission the viewer has for themselves on their own page' => ['own page', true],
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

    app(Tabs::class)
        ->user(TabDefinition::make('acme.visible')->view('usertabs::orders')->public())
        ->user(countedUserTab($this->calls)->authorize(function (Request $request, Model $record): bool {
            $this->calls->authorize++;

            throw new RuntimeException('authorize broke');
        }));

    // Renders the bar and checks ?tab= in one request.
    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.visible']))
        ->assertOk()
        ->assertSee('data-testid="tab-acme.visible"', false)
        ->assertDontSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(1);
    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'authorize broke');

    app()->forgetScopedInstances();

    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))->assertForbidden();

    app()->forgetScopedInstances();

    $this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))
        ->assertOk()
        ->assertViewHas('tab', 'overview')
        ->assertDontSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(3)
        ->and($this->calls->resolve)->toBe(0);
    Exceptions::assertReportedCount(3);
});

it('runs no closure when no tab is registered on the user screen', function (): void {
    app(Tabs::class)->organization(countedUserTab($this->calls)->authorize(countedUserAuthorize($this->calls, true)));

    $this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))->assertOk();
    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))->assertOk()->assertViewHas('tab', 'overview');

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
});

it('asks again on each request', function (): void {
    Exceptions::fake();
    $ada = user();
    $bob = user();

    app(Tabs::class)
        ->user(countedUserTab($this->calls)->authorize(function (Request $request, Model $record) use ($ada): bool {
            $this->calls->authorize++;

            return $request->user()?->is($ada) === true;
        }))
        ->user(TabDefinition::make('acme.broken')->view('usertabs::orders')->authorize(function (): bool {
            throw new RuntimeException('authorize broke');
        }));

    $this->actingAs($ada)->get(route('atrium.roster.users.show', $this->target->getRouteKey()))
        ->assertOk()
        ->assertSee('data-testid="tab-acme.orders"', false);

    expect($this->calls->authorize)->toBe(1);
    Exceptions::assertReportedCount(1);

    app()->forgetScopedInstances();

    $this->actingAs($bob)->get(route('atrium.roster.users.show', $this->target->getRouteKey()))
        ->assertOk()
        ->assertDontSee('data-testid="tab-', false);

    expect($this->calls->authorize)->toBe(2);
    Exceptions::assertReportedCount(2);
});

it('lets a throwing resolver propagate', function (): void {
    app(Tabs::class)->user(TabDefinition::make('acme.orders')->view('usertabs::orders')->public()->resolve(function (Model $record): array {
        throw new RuntimeException('resolver broke');
    }));

    $this->withoutExceptionHandling();

    expect(fn () => $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders'])))
        ->toThrow(RuntimeException::class, 'resolver broke');
});

it('does not load the overview\'s data while a registered tab is open', function (): void {
    app(Tabs::class)->user(countedUserTab($this->calls)->public());

    // Only the overview reads the SSO identities table.
    $table = Tables::name('sso_identities');
    $queries = collect();
    DB::listen(function (QueryExecuted $query) use ($queries): void {
        $queries->push($query->sql);
    });
    $reads = fn (): bool => $queries->contains(fn (string $sql): bool => str_contains($sql, $table));

    $this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))->assertOk()->assertViewHas('tab', 'overview');

    expect($reads())->toBeTrue();

    $queries->splice(0);
    app()->forgetScopedInstances();

    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))->assertOk()->assertViewHas('tab', 'acme.orders');

    expect($queries)->not->toBeEmpty()
        ->and($reads())->toBeFalse();
});

it('keeps the page\'s redirects free of tab=, landing on the overview', function (): void {
    app(Tabs::class)->user(countedUserTab($this->calls)->authorize(countedUserAuthorize($this->calls, true)));
    $show = route('atrium.roster.users.show', $this->target->getRouteKey());

    $saved = $this->patch(route('atrium.roster.users.profile', $this->target->getRouteKey()), ['bio' => 'Analyst'])
        ->assertRedirect($show);

    $refused = $this->from($show)
        ->post(route('atrium.roster.users.status', $this->target->getRouteKey()), ['status' => 'pending'])
        ->assertSessionHasErrors('status')
        ->assertRedirect($show);

    expect($saved->headers->get('Location'))->not->toContain('tab=')
        ->and($refused->headers->get('Location'))->not->toContain('tab=')
        // No tab visibility is asked while building a redirect.
        ->and($this->calls->authorize)->toBe(0);

    $landed = $this->get((string) $saved->headers->get('Location'))
        ->assertOk()
        ->assertViewHas('tab', 'overview')
        ->assertSee('data-testid="tab-acme.orders"', false)
        ->assertSee('data-testid="danger-zone"', false);

    expect(currentUserTabs($landed)[0] ?? '')->toContain('data-testid="tab-overview"');
});

it('keeps all-digit keys as strings', function (): void {
    app(Tabs::class)
        ->user(TabDefinition::make('123')->view('usertabs::orders')->public())
        ->user(TabDefinition::make('007')->view('usertabs::orders')->public());

    $this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))
        ->assertOk()
        ->assertSeeInOrder(['data-testid="tab-overview"', 'data-testid="tab-123"', 'data-testid="tab-007"'], false)
        ->assertViewHas('tabs', fn (array $tabs): bool => $tabs === ['overview', '123', '007']);

    foreach (['123', '007'] as $key) {
        $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => $key]))
            ->assertOk()
            ->assertViewHas('tab', fn (mixed $tab): bool => $tab === $key)
            ->assertSee('<span data-testid="host-tab">'.$key.'</span>', false);
    }
});

it('renders the deleted page and ignores ?tab= on a deleted user', function (): void {
    app(Tabs::class)->user(countedUserTab($this->calls)->authorize(countedUserAuthorize($this->calls, true)));
    $this->target->delete();

    $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => 'acme.orders']))
        ->assertOk()
        ->assertViewIs('roster::ui.users.deleted')
        ->assertSee('data-testid="deleted-banner"', false)
        ->assertDontSee('data-testid="tab-', false);

    expect($this->calls->authorize)->toBe(0)
        ->and($this->calls->resolve)->toBe(0);
});
