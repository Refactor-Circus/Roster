<?php

declare(strict_types=1);

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use Workbench\App\Models\User;

/*
 * The subjects of Roster's Atrium screens, as other packages' hooks receive
 * them (D-092): `subject_type` is the suite's name (`roster.organization`),
 * which no host's morph map holds, so each screen names its `subject_model`,
 * and Atrium sends hooks that model's morph type. The record a screen passes
 * is its route parameter, which Roster's pages fill with the model's route
 * key (an organization's slug), so a hook's API resolves it by route key.
 */

/**
 * Each subject screen's model, as Atrium reads its `subject_model` here, the
 * model's route key name and the screen's record parameter.
 *
 * @return array<string, array{string, string, string}>
 */
function rosterAtriumSubjects(): array
{
    /** @var array{screens: list<array{key: string, path: string, subject_type?: string, subject_model?: string|array{'$config': string, default: string}}>} $manifest */
    $manifest = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/atrium/src/integration.json'), true, flags: JSON_THROW_ON_ERROR);
    $subjects = [];

    foreach ($manifest['screens'] as $screen) {
        if (! isset($screen['subject_type'])) {
            continue;
        }

        $model = $screen['subject_model'] ?? null;
        $class = is_array($model) ? (config($model['$config']) ?? $model['default']) : $model;

        expect($class)->toBeString("[{$screen['key']}] names no subject model");

        /** @var class-string<Model> $class */
        expect(is_subclass_of($class, Model::class))->toBeTrue("[{$screen['key']}]'s subject model [{$class}] is no model");

        $subjects[$screen['key']] = [$class, (new $class)->getRouteKeyName(), (string) preg_replace('/^.*:/', '', $screen['path'])];
    }

    return $subjects;
}

it('names the model each hooked subject is stored as, routed by its route key', function (): void {
    expect(rosterAtriumSubjects())->toBe([
        'roster.user' => [User::class, 'id', 'user'],
        'roster.organization' => [OrganizationModel::class, 'slug', 'organization'],
    ]);
});

it('serves an organization by the slug its screen passes as the record', function (): void {
    $organization = OrganizationModel::query()->forceCreate(['name' => 'Acme', 'slug' => 'acme']);

    expect($organization->getRouteKey())->toBe('acme')
        ->and($organization->getKey())->not->toBe('acme');
});
