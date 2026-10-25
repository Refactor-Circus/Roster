<?php

declare(strict_types=1);

use RefactorCircus\Roster\Atrium\TabDefinition;
use RefactorCircus\Roster\Atrium\Tabs;
use RefactorCircus\Roster\Atrium\TabVisibility;

/**
 * The keys of a screen's registered tabs, as strings, in registration order.
 *
 * @param  array<array-key, TabDefinition>  $tabs
 * @return array<int, string>
 */
function registeredKeys(array $tabs): array
{
    return array_values(array_map(fn (TabDefinition $tab): string => $tab->key, $tabs));
}

it('is bound as a singleton', function (): void {
    expect(app(Tabs::class))->toBe(app(Tabs::class));
});

it('keeps the visibility memo per request', function (): void {
    $first = app(TabVisibility::class);

    expect(app(TabVisibility::class))->toBe($first);

    app()->forgetScopedInstances();

    expect(app(TabVisibility::class))->not->toBe($first);
});

it('returns the registry from organization() and user()', function (): void {
    $tabs = app(Tabs::class);

    expect($tabs->organization(TabDefinition::make('acme.orders')->view('acme::orders')))->toBe($tabs)
        ->and($tabs->user(TabDefinition::make('acme.orders')->view('acme::orders')))->toBe($tabs);
});

it('keeps each screen\'s tabs in registration order', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('b')->view('v'))
        ->organization(TabDefinition::make('a')->view('v'))
        ->organization(TabDefinition::make('c')->component('c'))
        ->user(TabDefinition::make('z')->view('v'));

    expect(registeredKeys(app(Tabs::class)->organizations()))->toBe(['b', 'a', 'c'])
        ->and(registeredKeys(app(Tabs::class)->users()))->toBe(['z']);
});

it('accepts keys matching the pattern', function (string $key): void {
    app(Tabs::class)
        ->organization(TabDefinition::make($key)->view('v'))
        ->user(TabDefinition::make($key)->view('v'));

    expect(registeredKeys(app(Tabs::class)->organizations()))->toBe([$key])
        ->and(registeredKeys(app(Tabs::class)->users()))->toBe([$key]);
})->with(['acme.billing', 'orders', 'a', '0x', 'acme_billing-2.v1']);

it('rejects keys outside the pattern', function (string $key, string $screen): void {
    expect(fn () => app(Tabs::class)->{$screen}(TabDefinition::make($key)->view('v')))
        ->toThrow(InvalidArgumentException::class);

    expect(app(Tabs::class)->organizations())->toBe([])
        ->and(app(Tabs::class)->users())->toBe([]);
})->with([
    'uppercase' => 'Acme.billing',
    'space' => 'acme billing',
    'slash' => 'acme/billing',
    'empty' => '',
    'leading dot' => '.acme',
    'leading dash' => '-acme',
    'other character' => 'acme!',
])->with(['organization', 'user']);

it('reads all-digit keys back as strings', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('123')->view('v'))
        ->organization(TabDefinition::make('007')->view('v'))
        ->user(TabDefinition::make('123')->view('v'))
        ->user(TabDefinition::make('007')->view('v'));

    foreach ([app(Tabs::class)->organizations(), app(Tabs::class)->users()] as $tabs) {
        expect(registeredKeys($tabs))->toBe(['123', '007'])
            ->and(array_map(strval(...), array_keys($tabs)))->toBe(['123', '007'])
            ->and($tabs['123']->key)->toBe('123')
            ->and($tabs['007']->key)->toBe('007');
    }
});

it('lists the built-in organization tabs as reserved keys', function (): void {
    expect(Tabs::ORGANIZATION_TABS)->toBe(['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'mcp', 'activity', 'settings'])
        ->and(Tabs::USER_DEFAULT)->toBe('overview');
});

it('rejects every built-in organization tab key, naming it', function (string $key): void {
    expect(fn () => app(Tabs::class)->organization(TabDefinition::make($key)->view('v')))
        ->toThrow(InvalidArgumentException::class, "[{$key}]");

    expect(app(Tabs::class)->organizations())->toBe([]);
})->with(Tabs::ORGANIZATION_TABS);

it('rejects overview on the user screen, naming it', function (): void {
    expect(fn () => app(Tabs::class)->user(TabDefinition::make('overview')->view('v')))
        ->toThrow(InvalidArgumentException::class, '[overview]');

    expect(app(Tabs::class)->users())->toBe([]);
});

it('reserves each screen\'s keys on that screen only', function (): void {
    app(Tabs::class)
        ->user(TabDefinition::make('members')->view('v'))
        ->organization(TabDefinition::make('overview')->view('v'));

    expect(registeredKeys(app(Tabs::class)->users()))->toBe(['members'])
        ->and(registeredKeys(app(Tabs::class)->organizations()))->toBe(['overview']);
});

it('rejects a duplicate key, naming the key and the screen', function (string $screen): void {
    app(Tabs::class)->{$screen}(TabDefinition::make('acme.orders')->view('first'));

    expect(fn () => app(Tabs::class)->{$screen}(TabDefinition::make('acme.orders')->view('second')))
        ->toThrow(function (InvalidArgumentException $exception) use ($screen): void {
            expect($exception->getMessage())->toContain('[acme.orders]')
                ->toContain($screen);
        });

    $registered = $screen === 'organization' ? app(Tabs::class)->organizations() : app(Tabs::class)->users();

    expect($registered['acme.orders']->view)->toBe('first');
})->with(['organization', 'user']);

it('allows the same key on both screens', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('acme.orders')->view('v'))
        ->user(TabDefinition::make('acme.orders')->view('v'));

    expect(registeredKeys(app(Tabs::class)->organizations()))->toBe(['acme.orders'])
        ->and(registeredKeys(app(Tabs::class)->users()))->toBe(['acme.orders']);
});

it('rejects a tab with neither a view nor a component, naming it', function (string $screen): void {
    expect(fn () => app(Tabs::class)->{$screen}(TabDefinition::make('acme.orders')->public()))
        ->toThrow(InvalidArgumentException::class, '[acme.orders]');
})->with(['organization', 'user']);

it('keeps its own copy of a registered tab', function (string $screen): void {
    $tab = TabDefinition::make('acme.orders')->view('acme::orders')->label('Orders');

    app(Tabs::class)->{$screen}($tab);

    $tab->component('acme-orders')->label('Changed')->icon('users');

    $registered = ($screen === 'organization' ? app(Tabs::class)->organizations() : app(Tabs::class)->users())['acme.orders'];

    expect($registered)->not->toBe($tab)
        ->and($registered->view)->toBe('acme::orders')
        ->and($registered->component)->toBeNull()
        ->and($registered->label)->toBe('Orders')
        ->and($registered->icon)->toBe('squares-2x2');
})->with(['organization', 'user']);

it('hands out copies, so a listed tab cannot change the registry', function (string $screen): void {
    app(Tabs::class)->{$screen}(TabDefinition::make('acme.orders')->view('acme::orders'));

    $list = fn (): array => $screen === 'organization' ? app(Tabs::class)->organizations() : app(Tabs::class)->users();

    $list()['acme.orders']->component('acme-orders')->label('Changed');

    expect($list()['acme.orders']->view)->toBe('acme::orders')
        ->and($list()['acme.orders']->label)->toBe('acme.orders');
})->with(['organization', 'user']);

it('forgets every registered tab on flush()', function (): void {
    app(Tabs::class)
        ->organization(TabDefinition::make('acme.orders')->view('v'))
        ->user(TabDefinition::make('acme.orders')->view('v'));

    app(Tabs::class)->flush();

    expect(app(Tabs::class)->organizations())->toBe([])
        ->and(app(Tabs::class)->users())->toBe([]);

    // A flushed key can be registered again.
    app(Tabs::class)->organization(TabDefinition::make('acme.orders')->view('v'));

    expect(registeredKeys(app(Tabs::class)->organizations()))->toBe(['acme.orders']);
});

it('validates and stores registrations while the UI is disabled', function (): void {
    config()->set('roster.ui.enabled', false);

    app(Tabs::class)->organization(TabDefinition::make('acme.orders')->view('v'));

    expect(registeredKeys(app(Tabs::class)->organizations()))->toBe(['acme.orders'])
        ->and(fn () => app(Tabs::class)->organization(TabDefinition::make('settings')->view('v')))
        ->toThrow(InvalidArgumentException::class);
});
