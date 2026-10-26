<?php

declare(strict_types=1);

use Illuminate\Support\Facades\View;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Roster\Atrium\TabDefinition;
use RefactorCircus\Roster\Atrium\Tabs;

beforeEach(function (): void {
    $this->actingAs(user());
    $this->organization = organization();
    $this->target = user();

    // The host's tab body, written where only these tests look.
    $directory = sys_get_temp_dir().'/roster-shared-tabs-'.getmypid();

    if (! is_dir($directory)) {
        mkdir($directory, 0777, true);
    }

    file_put_contents($directory.'/body.blade.php', '<p data-testid="shared-body">{{ $tab }}</p>');
    View::addNamespace('sharedtabs', $directory);

    // One fixture, registered on both screens.
    foreach (sharedTabBarFixture() as $tab) {
        app(Tabs::class)->organization($tab)->user($tab);
    }
});

/**
 * @return list<TabDefinition>
 */
function sharedTabBarFixture(): array
{
    return [
        TabDefinition::make('acme.orders')->label('Orders')->icon('shopping-cart')->view('sharedtabs::body')->public(),
        TabDefinition::make('acme.invoices')->label('<b>Invoices</b>')->icon('no-such-icon')->view('sharedtabs::body')->public(),
    ];
}

/**
 * Every tab bar link, as `data-testid` key => the link's tag and markup
 * without its href, in the order they render.
 *
 * @return array<string, string>
 */
function sharedTabBarLinks(TestResponse $response): array
{
    preg_match_all('/<a [^>]*data-testid="tab-([^"]+)"[^>]*>.*?<\/a>/s', (string) $response->getContent(), $matches, PREG_SET_ORDER);

    $links = [];

    foreach ($matches as $match) {
        $links[$match[1]] = (string) preg_replace('/ href="[^"]*"/', '', $match[0]);
    }

    return $links;
}

/**
 * The keys of the tab bar links carrying `aria-current="page"`.
 *
 * @return list<string>
 */
function sharedTabBarCurrent(TestResponse $response): array
{
    return array_map(strval(...), array_keys(array_filter(
        sharedTabBarLinks($response),
        fn (string $link): bool => (bool) preg_match('/^<a [^>]*aria-current="page"/', $link),
    )));
}

it('renders the same registered tabs on both screens', function (): void {
    $organization = sharedTabBarLinks($this->get(route('atrium.roster.organizations.show', $this->organization))->assertOk());
    $user = sharedTabBarLinks($this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))->assertOk());

    $registered = ['acme.orders', 'acme.invoices'];

    expect(array_map(strval(...), array_keys(array_diff_key($organization, array_flip(Tabs::ORGANIZATION_TABS)))))->toBe($registered)
        ->and(array_map(strval(...), array_keys(array_diff_key($user, [Tabs::USER_DEFAULT => true]))))->toBe($registered);

    // The same markup on both screens, apart from where each link goes.
    foreach ($registered as $key) {
        expect($user[$key])->toBe($organization[$key]);
    }
});

it('marks only the open tab as the current page on both screens', function (string $tab): void {
    $organization = $this->get(route('atrium.roster.organizations.show', [$this->organization, 'tab' => $tab]))
        ->assertOk()
        ->assertSee('<p data-testid="shared-body">'.$tab.'</p>', false);
    $user = $this->get(route('atrium.roster.users.show', [$this->target->getRouteKey(), 'tab' => $tab]))
        ->assertOk()
        ->assertSee('<p data-testid="shared-body">'.$tab.'</p>', false);

    expect(sharedTabBarCurrent($organization))->toBe([$tab])
        ->and(sharedTabBarCurrent($user))->toBe([$tab]);
})->with(['acme.orders', 'acme.invoices']);

it('marks each screen\'s default tab as the current page', function (): void {
    expect(sharedTabBarCurrent($this->get(route('atrium.roster.organizations.show', $this->organization))->assertOk()))->toBe(['members'])
        ->and(sharedTabBarCurrent($this->get(route('atrium.roster.users.show', $this->target->getRouteKey()))->assertOk()))->toBe(['overview']);
});
