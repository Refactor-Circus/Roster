<?php

declare(strict_types=1);

/*
 * Roster ships its Atrium front-end module: JS only, built from `atrium/`
 * into the committed `dist/atrium/` and declared in composer.json's
 * `extra.atrium` (with its integration manifest, `dist/atrium/integration.json`,
 * copied from `atrium/src/integration.json`), so hosts never run Node. The build writes `build.json`
 * with the SHA-256 of the files it was built from; this recomputes it the
 * way the Vite preset does (`hashInputs()` in Atrium's ui/sdk/vite, mirrored
 * by Atrium's BuildFreshnessTest), so a source changed without a rebuild
 * fails here with no Node needed. CI also rebuilds and diffs (the `ui` job).
 */

/**
 * The package root.
 */
function rosterPackageRoot(): string
{
    return dirname(__DIR__, 2);
}

/**
 * The SHA-256 of `inputs` under `root`, as the preset's `hashInputs()`
 * takes it: every file (directories walked, skipping `node_modules`, `dist`
 * and dot files), sorted by path, each as `path\0sha256\n` with CRLF read
 * as LF.
 *
 * @param  list<string>  $inputs
 */
function rosterModuleSourcesHash(string $root, array $inputs): string
{
    $files = [];

    $walk = function (string $path) use (&$walk, &$files, $root): void {
        $absolute = $root.'/'.$path;

        if (! file_exists($absolute)) {
            return;
        }

        if (is_dir($absolute)) {
            foreach (scandir($absolute) ?: [] as $entry) {
                if (! in_array($entry, ['node_modules', 'dist', '.DS_Store', '.git'], true) && ! str_starts_with($entry, '.')) {
                    $walk($path.'/'.$entry);
                }
            }

            return;
        }

        $files[] = $path;
    };

    foreach ($inputs as $input) {
        $walk(rtrim($input, '/'));
    }

    $files = array_values(array_unique($files));
    sort($files, SORT_STRING);

    $lines = array_map(
        fn (string $path): string => $path."\0".hash('sha256', str_replace("\r\n", "\n", (string) file_get_contents($root.'/'.$path)))."\n",
        $files,
    );

    return hash('sha256', implode('', $lines));
}

/**
 * The module's committed `build.json`.
 *
 * @return array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string}
 */
function rosterModuleBuild(): array
{
    /** @var array{key: string, shared_api: string, root: string, inputs: list<string>, hash: string} $build */
    $build = json_decode((string) file_get_contents(rosterPackageRoot().'/dist/atrium/build.json'), true, flags: JSON_THROW_ON_ERROR);

    return $build;
}

it('ships every script and stylesheet composer.json declares, from dist/atrium', function (): void {
    /** @var array{extra: array{atrium: array{key: string, scripts: list<string>, styles: list<string>}}} $composer */
    $composer = json_decode((string) file_get_contents(rosterPackageRoot().'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $module = $composer['extra']['atrium'];

    expect($module['key'])->toBe('roster')
        ->and($module['scripts'])->toBe(['dist/atrium/atrium.js'])
        ->and($module['styles'])->toBe(['dist/atrium/atrium.css']);

    foreach ([...$module['scripts'], ...$module['styles']] as $file) {
        expect(rosterPackageRoot().'/'.$file)->toBeFile();
    }
});

it('ships the integration manifest composer.json declares, keyed roster at version 1', function (): void {
    /** @var array{extra: array{atrium: array{integration?: string}}} $composer */
    $composer = json_decode((string) file_get_contents(rosterPackageRoot().'/composer.json'), true, flags: JSON_THROW_ON_ERROR);
    $path = $composer['extra']['atrium']['integration'] ?? null;

    expect($path)->toBe('dist/atrium/integration.json')
        ->and(rosterPackageRoot().'/'.$path)->toBeFile();

    /** @var array{version: int, key: string} $manifest */
    $manifest = json_decode((string) file_get_contents(rosterPackageRoot().'/'.$path), true, flags: JSON_THROW_ON_ERROR);

    expect($manifest['key'])->toBe('roster')
        ->and($manifest['version'])->toBe(1);
});

it('was built for the shared API 1.0 from the sources as they are now', function (): void {
    $build = rosterModuleBuild();
    $root = realpath(rosterPackageRoot().'/dist/atrium/'.$build['root']);

    expect($build['key'])->toBe('roster')
        ->and($build['shared_api'])->toBe('1.0')
        ->and($root)->toBe(rosterPackageRoot().'/atrium')
        ->and($build['inputs'])->toContain('src', 'package.json', 'pnpm-lock.yaml')
        ->and(rosterModuleSourcesHash((string) $root, $build['inputs']))
        ->toBe($build['hash'], 'dist/atrium is older than atrium/: run `pnpm build` in atrium/ and commit the result.');
});

it('ships dist/ but leaves the module sources out of the dist archive', function (): void {
    $attributes = (string) file_get_contents(rosterPackageRoot().'/.gitattributes');

    expect($attributes)->toMatch('#^/atrium\s+export-ignore$#m')
        ->not->toMatch('#^/dist\S*\s+export-ignore$#m');
});
