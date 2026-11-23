<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\Route;

/*
 * The committed OpenAPI document and TypeScript client (AC 21 to 24): both
 * must match the API Roster registers, so Atrium v2 and other clients build
 * on what the package serves. Regenerate them with `composer client:generate`.
 */

function rosterPackagePath(string $path = ''): string
{
    return dirname(__DIR__, 2).($path === '' ? '' : '/'.$path);
}

/**
 * @return array<string, mixed>
 */
function rosterJsonFile(string $path): array
{
    /** @var array<string, mixed> */
    return json_decode((string) file_get_contents(rosterPackagePath($path)), true, flags: JSON_THROW_ON_ERROR);
}

/**
 * Every operation of the committed document, as "operationId METHOD /path".
 *
 * @return list<string>
 */
function rosterDocumentedOperations(): array
{
    /** @var array<string, array<string, mixed>> $paths */
    $paths = rosterJsonFile('openapi.json')['paths'];
    $operations = [];

    foreach ($paths as $path => $item) {
        foreach ($item as $method => $operation) {
            if (in_array($method, ['get', 'put', 'post', 'delete', 'options', 'head', 'patch', 'trace'], true)) {
                /** @var array{operationId?: string} $operation */
                $operations[] = ($operation['operationId'] ?? '(none)').' '.strtoupper($method).' '.$path;
            }
        }
    }

    sort($operations);

    return $operations;
}

/**
 * Every registered `roster.*` route, keyed by name.
 *
 * @return array<string, RoutingRoute>
 */
function rosterNamedRoutes(): array
{
    Route::getRoutes()->refreshNameLookups();
    $routes = [];

    foreach (Route::getRoutes()->getRoutes() as $route) {
        if ($route instanceof RoutingRoute && str_starts_with((string) $route->getName(), 'roster.')) {
            $routes[(string) $route->getName()] = $route;
        }
    }

    return $routes;
}

/**
 * Every registered `roster.*` API route (the `api` middleware group), as
 * "name METHOD /path" relative to the `roster.routes.prefix` the document's
 * server carries.
 *
 * @return list<string>
 */
function rosterApiOperations(): array
{
    $prefix = trim((string) config('roster.routes.prefix'), '/');
    $operations = [];

    foreach (rosterNamedRoutes() as $name => $route) {
        if (! in_array('api', $route->middleware(), true)) {
            continue;
        }

        $uri = (string) preg_replace('#^'.preg_quote($prefix, '#').'(/|$)#', '', trim($route->uri(), '/'));

        foreach (array_diff($route->methods(), ['HEAD']) as $method) {
            $operations[] = "{$name} {$method} /{$uri}";
        }
    }

    sort($operations);

    return $operations;
}

it('documents every roster API route once, under its route name', function (): void {
    $routes = rosterApiOperations();

    expect($routes)->toContain(
        'roster.organizations.role-assignments.index GET /organizations/{organization}/role-assignments',
        'roster.invitations.lookup GET /invitations/{token}',
        'roster.stats.users GET /stats/users',
        'roster.stats.organizations GET /stats/organizations',
        'roster.search.users GET /search/users',
        'roster.search.organizations GET /search/organizations',
        'roster.history.index GET /history',
    )->and(rosterDocumentedOperations())->toBe($routes);
});

it('documents no web route', function (): void {
    $web = ['roster.invitations.show', 'roster.invitations.page.accept', 'roster.invitations.page.decline', 'roster.impersonation.enter', 'roster.impersonation.leave', 'roster.transfers.file'];
    $operationIds = array_map(fn (string $operation): string => explode(' ', $operation)[0], rosterDocumentedOperations());

    expect(array_keys(rosterNamedRoutes()))->toContain(...$web)
        ->and(array_intersect($web, $operationIds))->toBe([]);
});

it('is an OpenAPI 3.1 document of the Roster API 2.0.0', function (): void {
    $document = rosterJsonFile('openapi.json');

    expect($document['openapi'])->toStartWith('3.1')
        ->and($document['info'])->toMatchArray(['title' => 'Roster API', 'version' => '2.0.0']);
});

it('matches a fresh export of the workbench', function (): void {
    $path = (string) tempnam(sys_get_temp_dir(), 'roster-openapi-');

    try {
        $result = Process::path(rosterPackagePath())
            ->env(['TESTBENCH_WORKING_PATH' => rosterPackagePath()])
            ->timeout(300)
            ->run([PHP_BINARY, 'vendor/bin/testbench', 'workbench:openapi', $path]);

        expect($result->successful())->toBeTrue($result->errorOutput().$result->output())
            ->and((string) file_get_contents($path))
            ->toBe((string) file_get_contents(rosterPackagePath('openapi.json')), 'openapi.json is stale. Run composer client:generate.');
    } finally {
        @unlink($path);
    }
});

it('is pretty-printed with sorted keys', function (): void {
    $sorted = function (mixed $value) use (&$sorted): bool {
        if (! is_array($value)) {
            return true;
        }

        if (! array_is_list($value)) {
            $keys = array_map(strval(...), array_keys($value));
            $expected = $keys;
            sort($expected, SORT_STRING);

            if ($keys !== $expected) {
                return false;
            }
        }

        return array_all($value, fn (mixed $item): bool => $sorted($item));
    };

    $contents = (string) file_get_contents(rosterPackagePath('openapi.json'));

    expect($contents)->toStartWith("{\n    \"")
        ->and($contents)->toEndWith("}\n")
        ->and($sorted(rosterJsonFile('openapi.json')))->toBeTrue();
});

it('generated the client schema from the committed document', function (): void {
    $schema = (string) file_get_contents(rosterPackagePath('client/src/schema.d.ts'));
    $hash = hash_file('sha256', rosterPackagePath('openapi.json'));

    expect(strtok($schema, "\n"))->toBe("// openapi.json sha256: {$hash}", 'client/src/schema.d.ts is stale. Run composer client:generate.')
        ->and($schema)->toContain('export interface paths')
        ->and($schema)->toContain('export interface components');
});

it('describes the client package', function (): void {
    $package = rosterJsonFile('client/package.json');

    expect($package['name'])->toBe('@refactor-circus/roster-client')
        ->and($package['version'])->toBe('2.0.0')
        ->and($package['dependencies'])->toHaveKey('openapi-fetch')
        ->and($package['devDependencies'])->toHaveKeys(['openapi-typescript', 'typescript'])
        ->and($package['devDependencies'])->not->toHaveKey('openapi-fetch')
        ->and($package['scripts'])->toMatchArray(['generate' => 'node scripts/generate.mjs', 'typecheck' => 'tsc --noEmit'])
        ->and(file_exists(rosterPackagePath('client/package-lock.json')))->toBeTrue()
        ->and(file_exists(rosterPackagePath('client/scripts/generate.mjs')))->toBeTrue();
});

it('exports createRosterClient and the schema types', function (): void {
    $index = (string) file_get_contents(rosterPackagePath('client/src/index.ts'));

    expect($index)->toContain('export function createRosterClient(')
        ->and($index)->toContain('createClient<paths>(')
        ->and($index)->toMatch('/baseUrl: string;/')
        ->and($index)->toMatch('/fetch\?:/')
        ->and($index)->toMatch('/headers\?:/')
        ->and($index)->toMatch('/credentials\?:/')
        ->and($index)->toMatch('/export type \{[^}]*\bpaths\b[^}]*\} from [\'"]\.\/schema[\'"]/')
        ->and($index)->toMatch('/export type \{[^}]*\bcomponents\b[^}]*\} from [\'"]\.\/schema[\'"]/');
});

it('needs Scramble for the workbench only and regenerates through composer', function (): void {
    $composer = rosterJsonFile('composer.json');

    /** @var array<string, array<int, string>> $scripts */
    $scripts = $composer['scripts'];

    expect($composer['require-dev'])->toHaveKey('dedoc/scramble')
        ->and($composer['require'])->not->toHaveKey('dedoc/scramble')
        ->and($composer['suggest'] ?? [])->not->toHaveKey('dedoc/scramble')
        ->and($scripts['client:generate'])->toBe([
            '@php vendor/bin/testbench workbench:openapi openapi.json',
            'npm --prefix client ci',
            'npm --prefix client run generate',
        ]);
});

it('keeps Scramble out of the package code', function (): void {
    $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(rosterPackagePath('src'), FilesystemIterator::SKIP_DOTS));
    $offenders = [];

    foreach ($files as $file) {
        if ($file instanceof SplFileInfo && str_contains((string) file_get_contents($file->getPathname()), 'Dedoc\\Scramble')) {
            $offenders[] = $file->getPathname();
        }
    }

    expect($offenders)->toBe([]);
});

it('checks the client in CI', function (): void {
    $workflow = (string) file_get_contents(rosterPackagePath('.github/workflows/tests.yml'));

    expect($workflow)->toContain('  client:')
        ->and($workflow)->toContain('npm ci')
        ->and($workflow)->toContain('npm run generate')
        ->and($workflow)->toContain('npm run typecheck')
        ->and($workflow)->toContain('git diff --exit-code client/');
});

it('keeps client dependencies out of git', function (): void {
    expect((string) file_get_contents(rosterPackagePath('.gitignore')))->toContain('/client/node_modules');
});
