<?php

declare(strict_types=1);

use Illuminate\Filesystem\Filesystem;
use Illuminate\View\FileViewFinder;
use RefactorCircus\Keystone\Testing\Arch;
use RefactorCircus\Roster\Support\Tables;

const ROSTER_DOMAINS = __DIR__.'/../src/Domains';

const ROSTER_ACTIONS = [ROSTER_DOMAINS.'/*/Actions/*.php', __DIR__.'/../src/Transfers/Flows/Actions/*.php'];

arch()->preset()->php();

arch()->preset()->security();

arch('it will not use dd(), ddd(), env(), or exit()')
    ->expect(['dd', 'ddd', 'env', 'exit'])
    ->each->not->toBeUsed();

arch('the package source declares strict types')
    ->expect('RefactorCircus\Roster')
    ->toUseStrictTypes();

arch('actions are final classes')
    ->expect(Arch::domainNamespaces('RefactorCircus\Roster', ROSTER_DOMAINS, 'Actions'))
    ->classes()
    ->toBeFinal();

arch('actions take their input as Data objects, not arrays')
    ->expect(fn (): array => array_values(array_filter(
        array_map(function (string $action): ?string {
            foreach ((new ReflectionMethod($action, 'execute'))->getParameters() as $parameter) {
                $type = $parameter->getType();

                if ($type instanceof ReflectionNamedType && $type->getName() === 'array' && in_array($parameter->getName(), ['data', 'filters', 'input', 'attributes', 'values'], true)) {
                    return $action.'::execute($'.$parameter->getName().')';
                }
            }

            return null;
        }, Arch::domainClasses('RefactorCircus\Roster', ROSTER_DOMAINS, 'Actions')),
    )))
    ->toBeEmpty();

arch('action input validates itself')
    ->expect(fn (): array => Arch::dataWithoutRules(Arch::domainClasses('RefactorCircus\Roster', ROSTER_DOMAINS, 'Data')))
    ->toBeEmpty();

arch('package models are final, except the extendable owned-mode user')
    ->expect(Arch::domainNamespaces('RefactorCircus\Roster', ROSTER_DOMAINS, 'Models'))
    ->classes()
    ->toBeFinal()
    ->ignoring('RefactorCircus\Roster\Domains\User\Models\UserModel');

arch('every action is reachable from the HTTP API, MCP and a UI')
    ->expect(fn (): array => parityGaps())
    ->toBeEmpty();

arch('every API and MCP request names the permission it needs')
    ->expect([
        ...Arch::domainNamespaces('RefactorCircus\Roster', ROSTER_DOMAINS, 'Http/Requests'),
        ...Arch::domainNamespaces('RefactorCircus\Roster', ROSTER_DOMAINS, 'Mcp/Requests'),
    ])
    ->classes()
    ->toHaveMethod('ability');

arch('every action dispatches a starting and a finished event')
    ->expect(fn (): array => Arch::actionEventGaps(ROSTER_ACTIONS))
    ->toBeEmpty();

arch('actions write inside a transaction')
    ->expect(fn (): array => Arch::writesOutsideTransactions(ROSTER_ACTIONS))
    ->toBeEmpty();

arch('every model fires an event for every lifecycle hook')
    ->expect(fn (): array => Arch::modelEventGaps(Arch::domainClasses('RefactorCircus\Roster', ROSTER_DOMAINS, 'Models')))
    ->toBeEmpty();

arch('requests validate once, with the Data rules, and build from what they validated')
    ->expect(fn (): array => array_values(array_filter(
        [...(array) glob(ROSTER_DOMAINS.'/*/Http/Requests/*.php'), ...(array) glob(ROSTER_DOMAINS.'/*/Mcp/Requests/*.php')],
        fn (string $path): bool => str_contains((string) file_get_contents($path), 'Data::from('),
    )))
    ->toBeEmpty();

arch('every model has a policy in roster.policies')
    ->expect(fn (): array => Arch::modelsWithoutPolicies(
        Arch::domainClasses('RefactorCircus\Roster', ROSTER_DOMAINS, 'Models'),
        (require __DIR__.'/../config/roster.php')['policies'],
    ))
    ->toBeEmpty();

arch('tables are named through Tables, never as literals')
    ->expect(fn (): array => Arch::literalTableNames(
        [...array_values(array_filter(
            array_map(fn (SplFileInfo $file): string => $file->getPathname(), iterator_to_array(new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../src', FilesystemIterator::SKIP_DOTS)))),
            fn (string $path): bool => str_ends_with($path, '.php') && ! str_ends_with($path, 'Support/Tables.php'),
        )), ...(array) glob(__DIR__.'/../database/migrations/*.php'), ...(array) glob(__DIR__.'/../database/migrations/*/*.php')],
        array_values(array_diff(Tables::DEFAULTS, ['users'])),
    ))
    ->toBeEmpty();

/**
 * Every file under these package folders whose content contains one of the
 * strings, as "path: string".
 *
 * @param  array<int, string>  $folders
 * @param  array<int, string>  $needles
 * @param  array<int, string>  $except  Paths, relative to the package, left out.
 * @return array<int, string>
 */
function rosterFilesContaining(array $folders, array $needles, array $except = []): array
{
    $found = [];

    foreach ($folders as $folder) {
        $files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator(__DIR__.'/../'.$folder, FilesystemIterator::SKIP_DOTS));

        foreach ($files as $file) {
            /** @var SplFileInfo $file */
            $path = str_replace(__DIR__.'/../', '', $file->getPathname());

            foreach ($except as $excluded) {
                if ($path === $excluded || str_starts_with($path, rtrim($excluded, '/').'/')) {
                    continue 2;
                }
            }

            $content = (string) file_get_contents($file->getPathname());

            foreach ($needles as $needle) {
                if (str_contains($content, $needle)) {
                    $found[] = $path.': '.$needle;
                }
            }
        }
    }

    return $found;
}

arch('Roster uses nothing from Cortex: Cortex registers its own screens')
    ->expect('RefactorCircus\Cortex')
    ->not->toBeUsedIn('RefactorCircus\Roster');

arch('Roster ships no redirect-domains UI')
    ->expect(fn (): array => rosterFilesContaining(['src', 'resources'], ['redirect-domains', 'RedirectDomain']))
    ->toBeEmpty();

arch('Roster ships no audit trail or history navigation')
    ->expect(fn (): array => rosterFilesContaining(['src', 'resources'], ['historyNavItem', 'audit-trail']))
    ->toBeEmpty();

arch('no Roster view renders an Atrium audit component')
    ->expect(fn (): array => rosterFilesContaining(['resources/views'], ['x-atrium::audit']))
    ->toBeEmpty();

/**
 * Whether a `roster::` view exists in the package's own views, as
 * `view()->exists()` answers for it without an application.
 */
function rosterViewExists(string $name): bool
{
    $finder = new FileViewFinder(new Filesystem, []);
    $finder->addNamespace('roster', __DIR__.'/../resources/views');

    try {
        $finder->find($name);

        return true;
    } catch (InvalidArgumentException) {
        return false;
    }
}

arch('Roster\'s own tab registry is gone: screens declare themselves on Atrium\'s')
    ->expect(fn (): array => array_values(array_filter([
        ...array_map(
            fn (string $class): ?string => class_exists($class) ? $class : null,
            ['RefactorCircus\\Roster\\Atrium\\Tabs', 'RefactorCircus\\Roster\\Atrium\\TabDefinition', 'RefactorCircus\\Roster\\Atrium\\TabVisibility'],
        ),
        ...array_map(
            fn (string $view): ?string => rosterViewExists($view) ? $view : null,
            ['roster::ui.partials.tabs', 'roster::ui.partials.registered-tab'],
        ),
    ])))
    ->toBeEmpty();

// This file names them to check they are gone. The README and the Boost
// skill (resources/boost) are rewritten with the docs, which check them.
arch('nothing names Roster\'s old tab registry')
    ->expect(fn (): array => rosterFilesContaining(
        ['src', 'resources', 'tests'],
        ['Atrium\\Tabs', 'Roster\\Atrium\\TabDefinition', 'TabVisibility', 'partials.tabs', 'registered-tab'],
        ['tests/ArchTest.php', 'resources/boost', 'tests/Browser/Screenshots', 'tests/Browser/Traces'],
    ))
    ->toBeEmpty();
