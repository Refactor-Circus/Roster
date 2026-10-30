<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use RefactorCircus\Keystone\Testing\Arch;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;
use RefactorCircus\Roster\Support\Tables;
use RefactorCircus\Roster\Tests\TestCase;

const ROSTER_DOMAINS = __DIR__.'/../src/Domains';

const ROSTER_ACTIONS = [ROSTER_DOMAINS.'/*/Actions/*.php', __DIR__.'/../src/Transfers/Flows/Actions/*.php'];

/**
 * Roster's core: the classes that must name neither the SSO nor the SCIM
 * domain, which reach them only through the registration seams.
 */
const ROSTER_CORE = [
    'RefactorCircus\Roster\Domains\Permission\Services\Authorizer' => 'src/Domains/Permission/Services/Authorizer.php',
    'RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles' => 'src/Domains/Role/Support/BuiltInRoles.php',
    'RefactorCircus\Roster\Roster' => 'src/Roster.php',
    'RefactorCircus\Roster\Domains\Organization\Concerns\ManagesMemberships' => 'src/Domains/Organization/Concerns/ManagesMemberships.php',
    'RefactorCircus\Roster\Domains\Organization\Resources\MemberResource' => 'src/Domains/Organization/Resources/MemberResource.php',
    'RefactorCircus\Roster\Support\Audit' => 'src/Support/Audit.php',
    'RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource' => 'src/Domains/Organization/Enums/MembershipSource.php',
];

/**
 * The SSO and SCIM names core must not spell out: their permission prefixes
 * and the SCIM membership source, as quoted literals.
 */
const SSO_SCIM_PATTERNS = ['/\broster\.sso\b/', '/\broster\.scim\b/', "/'scim'/", '/"scim"/'];

/**
 * Every line of these files (relative to the package root) that matches a
 * pattern, as `file:line: text`.
 *
 * @param  array<int, string>  $files
 * @param  array<int, string>  $patterns
 * @return array<int, string>
 */
function linesMatching(array $files, array $patterns): array
{
    $hits = [];

    foreach ($files as $file) {
        foreach (file(__DIR__.'/../'.$file) ?: [] as $number => $line) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $line) === 1) {
                    $hits[] = $file.':'.($number + 1).': '.trim($line);

                    break;
                }
            }
        }
    }

    return $hits;
}

/**
 * The `policies` array of `config/roster.php`, as written in the file.
 */
function policiesSource(): string
{
    $config = (string) file_get_contents(__DIR__.'/../config/roster.php');

    return preg_match("/'policies'\s*=>\s*\[(.*?)\n    \],/s", $config, $match) === 1 ? $match[1] : '';
}

/**
 * The policies registered with the Gate once Roster has booted: the SSO and
 * SCIM domains register their models' policies themselves, outside
 * `roster.policies`. Boots the test application and tears it down again.
 *
 * @return array<class-string, class-string>
 */
function bootedGatePolicies(): array
{
    $case = new class('bootedGatePolicies') extends TestCase
    {
        /**
         * @return array<class-string, class-string>
         */
        public function gatePolicies(): array
        {
            $this->setUp();

            try {
                return Gate::policies();
            } finally {
                $this->tearDown();
            }
        }
    };

    return $case->gatePolicies();
}

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
        (require __DIR__.'/../config/roster.php')['policies'] + bootedGatePolicies(),
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

it('names core classes that exist', function (string $class): void {
    expect(class_exists($class) || interface_exists($class) || trait_exists($class) || enum_exists($class))->toBeTrue("{$class} does not exist")
        ->and(is_file(__DIR__.'/../'.ROSTER_CORE[$class]))->toBeTrue();
})->with(array_keys(ROSTER_CORE));

// One rule per class: with several targets, `not->toUse()` fails only when
// every one of them uses the namespace. Pest matches a target by name
// prefix, so `RefactorCircus\Roster\Roster` would also take in
// RosterServiceProvider, which is not one of the listed classes.
foreach (array_keys(ROSTER_CORE) as $class) {
    arch("{$class} uses no class of the SSO or SCIM domain")
        ->expect($class)
        ->not->toUse(['RefactorCircus\Roster\Domains\Sso', 'RefactorCircus\Roster\Domains\Scim'])
        ->ignoring('RefactorCircus\Roster\RosterServiceProvider');
}

it('has no MembershipSource::Scim, and nothing uses it', function (): void {
    $files = array_filter(
        [__DIR__.'/../src', __DIR__.'/../database', __DIR__.'/../resources', __DIR__.'/../config', __DIR__.'/../workbench'],
        is_dir(...),
    );

    $uses = [];

    foreach ($files as $directory) {
        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory, FilesystemIterator::SKIP_DOTS)) as $file) {
            /** @var SplFileInfo $file */
            if ($file->getExtension() === 'php' && preg_match('/MembershipSource::Scim\b/', (string) file_get_contents($file->getPathname())) === 1) {
                $uses[] = $file->getPathname();
            }
        }
    }

    expect(defined(MembershipSource::class.'::Scim'))->toBeFalse()
        ->and(MembershipSource::tryFrom('scim'))->toBeNull()
        ->and($uses)->toBe([]);
});

it('spells out no SSO or SCIM name in core', function (): void {
    expect(linesMatching([...array_values(ROSTER_CORE), 'src/Domains/Permission/Services/Resources.php'], SSO_SCIM_PATTERNS))->toBe([]);
});

it('catches each SSO and SCIM name it looks for', function (string $line, bool $caught): void {
    $matches = array_filter(SSO_SCIM_PATTERNS, fn (string $pattern): bool => preg_match($pattern, $line) === 1);

    expect($matches !== [])->toBe($caught);
})->with([
    'sso permission' => ["'roster.sso.view' => 'View SSO.',", true],
    'scim prefix' => ['$resources->rosterResource(\'roster.scim\', $model);', true],
    'single-quoted source' => ["\$this->join(\$organization, \$user, 'scim');", true],
    'double-quoted source' => ['$source === "scim"', true],
    'a longer prefix' => ["'roster.ssohelp.view'", false],
    'a longer source' => ["'scimitar'", false],
    'a class name' => ['ScimTokenModel::class', false],
]);

it('lists no SSO or SCIM model in the config policies', function (): void {
    $policies = policiesSource();

    expect($policies)->toContain('UserModel::class')
        ->and(preg_match_all('/\b(sso|scim)/i', $policies))->toBe(0)
        ->and(array_values(array_filter(
            array_keys((require __DIR__.'/../config/roster.php')['policies']),
            fn (string $model): bool => preg_match('/\\\\(Sso|Scim)\\\\/', $model) === 1,
        )))->toBe([]);
});
