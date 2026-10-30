<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;

it('is bound as a singleton', function (): void {
    expect(app(MembershipSources::class))->toBe(app(MembershipSources::class));
});

it('holds the built-in sources from the start', function (): void {
    $sources = new MembershipSources;

    expect(array_column(MembershipSource::cases(), 'value'))->toBe(['direct', 'invitation', 'domain', 'personal', 'import']);

    foreach (MembershipSource::cases() as $case) {
        expect($sources->has($case->value))->toBeTrue()
            ->and($sources->label($case->value))->toBe($case->value);
    }

    expect($sources->all())->toBe([
        'direct' => 'direct',
        'invitation' => 'invitation',
        'domain' => 'domain',
        'personal' => 'personal',
        'import' => 'import',
    ]);
});

it('registers a source with its label and returns itself', function (): void {
    $sources = new MembershipSources;

    expect($sources->register('acme-sync', 'Acme sync'))->toBe($sources)
        ->and($sources->has('acme-sync'))->toBeTrue()
        ->and($sources->label('acme-sync'))->toBe('Acme sync')
        ->and($sources->all())->toHaveKey('acme-sync', 'Acme sync');
});

it('shows a value nothing registers as-is', function (): void {
    $sources = new MembershipSources;

    expect($sources->has('legacy-crm'))->toBeFalse()
        ->and($sources->label('legacy-crm'))->toBe('legacy-crm');
});

it('rejects a source registered twice, naming it', function (): void {
    $sources = (new MembershipSources)->register('acme-sync', 'Acme sync');

    expect(fn () => $sources->register('acme-sync', 'Other'))
        ->toThrow(InvalidArgumentException::class, '[acme-sync]')
        ->and($sources->label('acme-sync'))->toBe('Acme sync');
});

it('rejects registering a built-in source, naming it', function (MembershipSource $case): void {
    expect(fn () => (new MembershipSources)->register($case->value, 'Mine'))
        ->toThrow(InvalidArgumentException::class, "[{$case->value}]");
})->with(MembershipSource::cases());

it('rejects a value the source column cannot hold, naming it', function (string $source): void {
    expect(fn () => (new MembershipSources)->register($source, 'Label'))
        ->toThrow(InvalidArgumentException::class, "[{$source}]");
})->with([
    'empty' => '',
    'longer than 32 characters' => str_repeat('a', 33),
]);

it('no longer registers scim, and shows a stored scim as-is', function (): void {
    expect(app(MembershipSources::class)->has('scim'))->toBeFalse()
        ->and(app(MembershipSources::class)->label('scim'))->toBe('scim');
});
