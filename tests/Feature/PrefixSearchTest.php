<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\PrefixSearch;

it('escapes the LIKE wildcards and its own escape character, then appends %', function (string $value, string $pattern): void {
    expect(PrefixSearch::pattern($value))->toBe($pattern);
})->with([
    'plain' => ['Acme', 'Acme%'],
    'percent' => ['100%', '100!%%'],
    'underscore' => ['a_b', 'a!_b%'],
    'escape character' => ['Hi!', 'Hi!!%'],
    'backslash' => ['a\\b', 'a\\b%'],
]);

it('matches rows whose columns start with the value, wildcards literally', function (): void {
    organization(attributes: ['name' => '100% Pure', 'slug' => 'pure']);
    organization(attributes: ['name' => '100 Proof', 'slug' => 'proof']);
    organization(attributes: ['name' => 'Underscored', 'slug' => 'a_b']);
    organization(attributes: ['name' => 'Lettered', 'slug' => 'axb']);
    organization(attributes: ['name' => 'Hi! there', 'slug' => 'hi']);
    organization(attributes: ['name' => 'Hix', 'slug' => 'hix']);

    $names = fn (string $q): array => PrefixSearch::apply(OrganizationModel::query(), ['name', 'slug'], $q)
        ->orderBy('name')
        ->pluck('name')
        ->all();

    expect($names('100%'))->toBe(['100% Pure'])
        ->and($names('a_'))->toBe(['Underscored'])
        ->and($names('Hi!'))->toBe(['Hi! there'])
        ->and($names('nothing'))->toBe([]);
});
