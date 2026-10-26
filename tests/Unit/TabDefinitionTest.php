<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Roster\Atrium\TabDefinition;
use RefactorCircus\Roster\Atrium\TabVisibility;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use Workbench\App\Models\User;

function tabRecord(int $id = 1): OrganizationModel
{
    return (new OrganizationModel)->forceFill(['id' => $id]);
}

function tabSelf(int $id = 1): User
{
    return (new User)->forceFill(['id' => $id]);
}

describe('state', function (): void {
    it('defaults the label to the key and the icon to squares-2x2', function (): void {
        $tab = TabDefinition::make('acme.orders');

        expect($tab->key)->toBe('acme.orders')
            ->and($tab->label)->toBe('acme.orders')
            ->and($tab->icon)->toBe('squares-2x2')
            ->and($tab->view)->toBeNull()
            ->and($tab->component)->toBeNull()
            ->and($tab->permission)->toBeNull()
            ->and($tab->public)->toBeFalse();
    });

    it('is built with fluent setters that return the same definition', function (): void {
        $tab = TabDefinition::make('acme.orders');

        expect($tab->label('Orders'))->toBe($tab)
            ->and($tab->icon('shopping-cart'))->toBe($tab)
            ->and($tab->view('acme::orders'))->toBe($tab)
            ->and($tab->permission('acme.orders.view'))->toBe($tab)
            ->and($tab->resolve(fn (Model $record): array => []))->toBe($tab)
            ->and($tab->authorize(fn (Request $request, Model $record): bool => true))->toBe($tab)
            ->and($tab->public())->toBe($tab);

        expect($tab->label)->toBe('Orders')
            ->and($tab->icon)->toBe('shopping-cart')
            ->and($tab->view)->toBe('acme::orders')
            ->and($tab->permission)->toBe('acme.orders.view')
            ->and($tab->public)->toBeTrue();
    });

    it('clears the view when a component is set', function (): void {
        $tab = TabDefinition::make('acme.orders')->view('a')->component('b');

        expect($tab->view)->toBeNull()
            ->and($tab->component)->toBe('b');
    });

    it('clears the component when a view is set', function (): void {
        $tab = TabDefinition::make('acme.orders')->component('b')->view('a');

        expect($tab->component)->toBeNull()
            ->and($tab->view)->toBe('a');
    });

    it('cannot be assigned from outside', function (string $property, mixed $value): void {
        $tab = TabDefinition::make('acme.orders');

        expect(fn () => $tab->{$property} = $value)->toThrow(Error::class);
    })->with([
        'key' => ['key', 'other'],
        'label' => ['label', 'Other'],
        'icon' => ['icon', 'users'],
        'view' => ['view', 'other'],
        'component' => ['component', 'other'],
        'permission' => ['permission', 'other'],
        'public' => ['public', true],
    ]);

    it('keeps its resolver and authorize closures private', function (string $property): void {
        $tab = TabDefinition::make('acme.orders')
            ->resolve(fn (Model $record): array => [])
            ->authorize(fn (Request $request, Model $record): bool => true);

        expect(fn () => $tab->{$property})->toThrow(Error::class);
    })->with(['resolver', 'authorize']);

    it('rejects a key outside the pattern on construction', function (string $key): void {
        expect(fn () => TabDefinition::make($key))->toThrow(InvalidArgumentException::class);
    })->with(['Acme', 'acme billing', 'acme/billing', '', '.acme']);
});

describe('resolveData', function (): void {
    it('is empty without a resolver', function (): void {
        expect(TabDefinition::make('acme.orders')->resolveData(tabRecord()))->toBe([]);
    });

    it('passes the record to the resolver and returns its data', function (): void {
        $record = tabRecord(7);
        $tab = TabDefinition::make('acme.orders')->resolve(fn (Model $given): array => ['id' => $given->getKey(), 'same' => $given === $record]);

        expect($tab->resolveData($record))->toBe(['id' => 7, 'same' => true]);
    });

    it('lets a throwing resolver propagate', function (): void {
        $tab = TabDefinition::make('acme.orders')->resolve(fn (Model $record): array => throw new RuntimeException('resolver broke'));

        expect(fn () => $tab->resolveData(tabRecord()))->toThrow(RuntimeException::class, 'resolver broke');
    });
});

describe('allows', function (): void {
    it('hides a tab with neither a permission nor a closure', function (): void {
        expect(TabDefinition::make('acme.orders')->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse();
    });

    it('shows a public tab to anyone who may open the screen', function (): void {
        expect(TabDefinition::make('acme.orders')->public()->allows(request(), tabRecord(), tabRecord(), null))->toBeTrue();
    });

    it('asks the closure with the request and the record', function (bool $answer): void {
        $record = tabRecord();
        $seen = [];
        $tab = TabDefinition::make('acme.orders')->authorize(function (Request $request, Model $given) use (&$seen, $answer, $record): bool {
            $seen = [$request === request(), $given === $record];

            return $answer;
        });

        expect($tab->allows(request(), $record, $record, null))->toBe($answer)
            ->and($seen)->toBe([true, true]);
    })->with([true, false]);

    it('checks the permission through the screen access check', function (): void {
        $tab = TabDefinition::make('acme.orders')->permission('roster.members.view');

        // Surface tests run with roster.authorization off: every check passes.
        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeTrue();

        // A guest holds no permission once the check is on.
        config()->set('roster.authorization', true);

        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse();
    });

    it('needs both the permission and the closure when it has both', function (): void {
        config()->set('roster.authorization', true);
        $calls = 0;
        $tab = TabDefinition::make('acme.orders')
            ->permission('roster.members.view')
            ->authorize(function () use (&$calls): bool {
                $calls++;

                return true;
            });

        // The permission fails first, so the closure never runs.
        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse()
            ->and($calls)->toBe(0);

        config()->set('roster.authorization', false);

        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeTrue()
            ->and($calls)->toBe(1);

        $denied = TabDefinition::make('acme.orders')->permission('roster.members.view')->authorize(fn (): bool => false);

        expect($denied->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse();
    });

    it('counts anything but true from the closure as denied', function (): void {
        $tab = TabDefinition::make('acme.orders')->authorize(fn (): mixed => 1);

        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse();
    });

    it('denies a throwing closure and reports it once per request per key', function (): void {
        Exceptions::fake();
        $calls = 0;
        $tab = TabDefinition::make('acme.orders')->authorize(function () use (&$calls): bool {
            $calls++;

            throw new RuntimeException('authorize broke');
        });

        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse()
            ->and($tab->allows(request(), tabRecord(2), tabRecord(2), null))->toBeFalse()
            ->and($calls)->toBe(2);

        Exceptions::assertReportedCount(1);
        Exceptions::assertReported(fn (RuntimeException $exception): bool => $exception->getMessage() === 'authorize broke');

        // A new request reports it again.
        app()->forgetScopedInstances();

        expect($tab->allows(request(), tabRecord(), tabRecord(), null))->toBeFalse();

        Exceptions::assertReportedCount(2);
    });
});

describe('the per-request memo', function (): void {
    it('asks a tab once per screen, record and key', function (): void {
        $calls = 0;
        $tab = TabDefinition::make('acme.orders')->authorize(function () use (&$calls): bool {
            $calls++;

            return true;
        });
        $visibility = app(TabVisibility::class);

        expect($visibility->allows('organization', $tab, request(), tabRecord(), tabRecord(), null))->toBeTrue()
            ->and($visibility->allows('organization', $tab, request(), tabRecord(), tabRecord(), null))->toBeTrue()
            ->and($calls)->toBe(1);

        // Another record, or the same key on the other screen, is its own answer.
        $visibility->allows('organization', $tab, request(), tabRecord(2), tabRecord(2), null);
        $visibility->allows('user', $tab, request(), tabSelf(), null, tabSelf());

        expect($calls)->toBe(3);
    });

    it('keeps one answer per tab key on a record', function (): void {
        $visibility = app(TabVisibility::class);
        $shown = TabDefinition::make('acme.shown')->public();
        $hidden = TabDefinition::make('acme.hidden');

        expect($visibility->allows('organization', $shown, request(), tabRecord(), tabRecord(), null))->toBeTrue()
            ->and($visibility->allows('organization', $hidden, request(), tabRecord(), tabRecord(), null))->toBeFalse();
    });

    it('is rebuilt for each request', function (): void {
        $calls = 0;
        $tab = TabDefinition::make('acme.orders')->authorize(function () use (&$calls): bool {
            $calls++;

            return true;
        });

        app(TabVisibility::class)->allows('organization', $tab, request(), tabRecord(), tabRecord(), null);
        app()->forgetScopedInstances();
        app(TabVisibility::class)->allows('organization', $tab, request(), tabRecord(), tabRecord(), null);

        expect($calls)->toBe(2);
    });

    it('runs and reports a throwing closure once when asked twice', function (): void {
        Exceptions::fake();
        $calls = 0;
        $tab = TabDefinition::make('acme.orders')->authorize(function () use (&$calls): bool {
            $calls++;

            throw new RuntimeException('authorize broke');
        });
        $visibility = app(TabVisibility::class);

        expect($visibility->allows('organization', $tab, request(), tabRecord(), tabRecord(), null))->toBeFalse()
            ->and($visibility->allows('organization', $tab, request(), tabRecord(), tabRecord(), null))->toBeFalse()
            ->and($calls)->toBe(1);

        Exceptions::assertReportedCount(1);
    });

    it('reports a throwing closure once per tab key, whatever the screen', function (): void {
        Exceptions::fake();
        $tab = TabDefinition::make('acme.orders')->authorize(fn (): bool => throw new RuntimeException('authorize broke'));
        $visibility = app(TabVisibility::class);

        expect($visibility->allows('organization', $tab, request(), tabRecord(), tabRecord(), null))->toBeFalse()
            ->and($visibility->allows('user', $tab, request(), tabSelf(), null, tabSelf()))->toBeFalse();

        Exceptions::assertReportedCount(1);
    });
});
