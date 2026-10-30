<?php

declare(strict_types=1);

use Illuminate\Routing\Route as RegisteredRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Testing\TestResponse;
use RefactorCircus\Atrium\Domains\Screens\Data\ResolvedScreen;
use RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition;
use RefactorCircus\Keystone\Support\Surface;
use RefactorCircus\Roster\Domains\DomainServiceProvider;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Support\Tables;

/**
 * Criterion 12's pattern, verbatim: snake_case, kebab-case, a leading word,
 * camelCase and PascalCase identifiers and upper-case constants naming SSO
 * or SCIM, but not words such as "association".
 */
const REMOVED_SSO_SCIM = '/(?<![A-Za-z])(?:sso|scim)|Sso|Scim|(?<![A-Z])(?:SSO|SCIM)(?![A-Z])/';

/**
 * The five SSO and SCIM table keys Roster no longer owns.
 */
const REMOVED_SSO_SCIM_TABLES = ['sso_connections', 'sso_identities', 'scim_tokens', 'scim_users', 'scim_groups'];

/**
 * Every line under these package folders that matches a pattern, as
 * `path:line: text` relative to the package root.
 *
 * @param  list<string>  $folders
 * @param  list<string>  $skip  paths (relative to the package root) to leave out
 * @return list<string>
 */
function removedSsoScimHits(array $folders, string $pattern, array $skip = []): array
{
    $root = dirname(__DIR__, 2);
    $hits = [];

    foreach ($folders as $folder) {
        if (! is_dir($root.'/'.$folder)) {
            continue;
        }

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/'.$folder, FilesystemIterator::SKIP_DOTS)) as $file) {
            /** @var SplFileInfo $file */
            $path = substr($file->getPathname(), strlen($root) + 1);

            if (array_filter($skip, fn (string $prefix): bool => str_starts_with($path, $prefix)) !== []) {
                continue;
            }

            foreach (file($file->getPathname()) ?: [] as $number => $line) {
                if (preg_match($pattern, $line) === 1) {
                    $hits[] = $path.':'.($number + 1).': '.trim($line);
                }
            }
        }
    }

    return $hits;
}

/**
 * The organization screen as resolved for a request.
 */
function removedSsoScimScreen(TestResponse $response): ResolvedScreen
{
    $resolved = $response->viewData('resolved');

    expect($resolved)->toBeInstanceOf(ResolvedScreen::class);

    return $resolved;
}

/**
 * Turn authorization on and sign in a user holding these permissions in
 * the organization.
 *
 * @param  list<string>  $permissions
 */
function removedSsoScimViewer(OrganizationModel $organization, array $permissions): void
{
    config()->set('roster.authorization', true);
    $viewer = user();

    RoleAssignmentModel::query()->create([
        'role_id' => roleWith($permissions, 'organization')->id,
        'user_id' => $viewer->getKey(),
        'organization_id' => $organization->getKey(),
    ]);

    app(Permissions::class)->flush();
    test()->actingAs($viewer);
}

// Criterion 1: the domains are gone.

it('has no SSO or SCIM domain folder, provider, factory or MCP trait', function (): void {
    $root = dirname(__DIR__, 2);

    expect(is_dir($root.'/src/Domains/Sso'))->toBeFalse()
        ->and(is_dir($root.'/src/Domains/Scim'))->toBeFalse()
        ->and(is_file($root.'/database/factories/SsoConnectionFactory.php'))->toBeFalse()
        ->and(is_file($root.'/src/Mcp/Concerns/DescribesSsoConnection.php'))->toBeFalse()
        ->and(class_exists('RefactorCircus\Roster\Domains\Sso\SsoServiceProvider'))->toBeFalse()
        ->and(class_exists('RefactorCircus\Roster\Domains\Scim\ScimServiceProvider'))->toBeFalse()
        ->and(trait_exists('RefactorCircus\Roster\Mcp\Concerns\DescribesSsoConnection'))->toBeFalse()
        ->and(class_exists('RefactorCircus\Roster\Database\Factories\SsoConnectionFactory'))->toBeFalse();
});

it('registers no SSO or SCIM domain provider', function (): void {
    $providers = (new ReflectionProperty(DomainServiceProvider::class, 'providers'))->getValue(new DomainServiceProvider(app()));

    expect($providers)->toBeArray()->not->toBeEmpty()
        ->and(array_values(preg_grep(REMOVED_SSO_SCIM, (array) $providers) ?: []))->toBe([])
        ->and(array_values(preg_grep(REMOVED_SSO_SCIM, array_keys(app()->getLoadedProviders())) ?: []))->toBe([]);
});

it('opens Guardian\'s handover gate: the old SSO connection model is not a loadable class', function (): void {
    expect(class_exists('RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel'))->toBeFalse();
});

arch('no class in Roster uses the SSO or SCIM domain')
    ->expect('RefactorCircus\Roster')
    ->not->toUse(['RefactorCircus\Roster\Domains\Sso', 'RefactorCircus\Roster\Domains\Scim']);

// Criterion 2: the roster.sso / roster.scim registrations are gone.

it('registers no roster.sso or roster.scim resource', function (): void {
    expect(app(Resources::class)->model('roster.sso'))->toBeNull()
        ->and(app(Resources::class)->model('roster.scim'))->toBeNull()
        ->and(app(Resources::class)->model('roster.organizations'))->not->toBeNull();
});

it('catalogues no roster.sso. or roster.scim. permission', function (): void {
    $names = array_keys(app(PermissionCatalogue::class)->all());

    expect(array_values(array_filter(
        $names,
        fn (string $name): bool => str_starts_with($name, 'roster.sso.') || str_starts_with($name, 'roster.scim.'),
    )))->toBe([]);
});

it('denies roster.sso.view to an organization admin who is not a super-admin', function (): void {
    config()->set('roster.authorization', true);
    $admin = user();
    $acme = organization(attributes: ['name' => 'Acme']);
    grant($admin, 'admin', $acme);

    // The admin can use the organization, so the denial is about the prefix.
    expect(app(Authorizer::class)->check($admin, 'roster.organizations.update', $acme))->toBeTrue()
        ->and(app(Authorizer::class)->check($admin, 'roster.sso.view', $acme))->toBeFalse()
        ->and(app(Authorizer::class)->check($admin, 'roster.scim.manage', $acme))->toBeFalse();
});

it('registers no scim membership source', function (): void {
    expect(app(MembershipSources::class)->has('scim'))->toBeFalse();
});

// Criterion 5: the SSO and SCIM routes are gone.

it('has no route named :dataset', function (string $name): void {
    expect(Route::has($name))->toBeFalse();
})->with([
    'roster.organizations.sso-connections.index',
    'roster.organizations.sso-connections.store',
    'roster.sso-connections.show',
    'roster.sso-connections.update',
    'roster.sso-connections.destroy',
    'roster.users.sso-identities.index',
    'roster.sso-identities.destroy',
    'roster.organizations.scim-tokens.index',
    'roster.organizations.scim-tokens.store',
    'roster.scim-tokens.destroy',
    'roster.sso.discover',
    'roster.sso.start',
    'roster.sso.callback',
    'roster.sso.metadata',
    'roster.sso.link',
    'atrium.roster.sso.store',
    'atrium.roster.sso.show',
    'atrium.roster.sso.update',
    'atrium.roster.sso.destroy',
    'atrium.roster.sso-identities.destroy',
    'atrium.roster.scim-tokens.store',
    'atrium.roster.scim-tokens.revoke',
]);

it('has no route whose name or URI names SSO or SCIM, named or not', function (): void {
    $routes = collect(Route::getRoutes()->getRoutes());

    // The positive control: Roster's API and Atrium routes are mounted.
    expect(Route::has('roster.organizations.members.index'))->toBeTrue()
        ->and(Route::has('atrium.roster.organizations.show'))->toBeTrue()
        ->and($routes->map(fn (RegisteredRoute $route): string => $route->uri())->filter(fn (string $uri): bool => preg_match(REMOVED_SSO_SCIM, $uri) === 1)->values()->all())->toBe([])
        ->and($routes->map(fn (RegisteredRoute $route): string => (string) $route->getName())->filter(fn (string $name): bool => preg_match(REMOVED_SSO_SCIM, $name) === 1)->values()->all())->toBe([])
        ->and($routes->map(fn (RegisteredRoute $route): string => (string) $route->getName())->filter(fn (string $name): bool => str_starts_with($name, 'roster.scim.'))->values()->all())->toBe([]);
});

it('names only the web surface for Roster\'s routes, and no scim one', function (): void {
    /** @var array<string, string> $routes */
    $routes = (new ReflectionProperty(Surface::class, 'routes'))->getValue(app(Surface::class));
    $roster = array_filter($routes, fn (string $prefix): bool => str_starts_with($prefix, 'roster.'), ARRAY_FILTER_USE_KEY);

    expect($roster)->not->toBeEmpty()
        ->and(array_values(array_unique($roster)))->toBe(['web'])
        ->and($routes)->not->toContain('scim')
        ->and(array_values(preg_grep(REMOVED_SSO_SCIM, array_keys($routes)) ?: []))->toBe([]);
});

// Criterion 6: the MCP tools are gone.

it('lists no MCP tool naming SSO or SCIM', function (): void {
    $tools = collect(RosterServer::TOOLS);
    $names = $tools->map(fn (string $tool): string => app($tool)->name());

    expect($names)->toContain('list-members-tool')
        ->and($names->filter(fn (string $name): bool => preg_match(REMOVED_SSO_SCIM, $name) === 1)->values()->all())->toBe([])
        ->and($tools->filter(fn (string $tool): bool => preg_match(REMOVED_SSO_SCIM, class_basename($tool)) === 1)->values()->all())->toBe([]);
});

// Criterion 7: the organization screen loses its sso and scim tabs.

it('declares exactly members, teams, invitations, roles and settings, with members the default', function (): void {
    $this->actingAs(user());
    $organization = organization(attributes: ['name' => 'Acme']);

    $resolved = removedSsoScimScreen($this->get(route('atrium.roster.organizations.show', $organization))->assertOk());

    expect($resolved->screen->key)->toBe('roster.organization')
        ->and(array_map(fn (TabDefinition $tab): string => $tab->key, $resolved->tabs))->toBe(['members', 'teams', 'invitations', 'roles', 'settings'])
        ->and(array_map(fn (TabDefinition $tab): string => $tab->icon, $resolved->tabs))->not->toContain('arrow-right-end-on-rectangle')->not->toContain('server-stack')
        ->and($resolved->openTab)->toBe('members');
});

it('opens members for ?tab=:dataset, for a viewer holding every organization permission', function (string $tab): void {
    $organization = organization(attributes: ['name' => 'Acme']);
    removedSsoScimViewer($organization, PermissionModel::query()->pluck('name')->all());

    $response = $this->get(route('atrium.roster.organizations.show', [$organization, 'tab' => $tab]))->assertOk();

    expect(removedSsoScimScreen($response)->openTab)->toBe('members');

    $response->assertDontSee('data-testid="tab-sso"', false)
        ->assertDontSee('data-testid="tab-scim"', false)
        ->assertSee('data-testid="tab-members"', false);
})->with(['sso', 'scim']);

it('no longer gates ?tab=sso: a viewer without roster.sso.view gets members', function (): void {
    $organization = organization(attributes: ['name' => 'Acme']);
    removedSsoScimViewer($organization, ['roster.organizations.view', 'roster.members.view']);

    $response = $this->get(route('atrium.roster.organizations.show', [$organization, 'tab' => 'sso']))->assertOk();

    expect(removedSsoScimScreen($response)->openTab)->toBe('members');
});

it('has no SSO or SCIM Atrium controller or page', function (): void {
    expect(class_exists('RefactorCircus\Roster\Atrium\Http\Controllers\SsoUiController'))->toBeFalse()
        ->and(class_exists('RefactorCircus\Roster\Atrium\Http\Controllers\ScimUiController'))->toBeFalse()
        ->and(is_dir(dirname(__DIR__, 2).'/resources/views/ui/sso'))->toBeFalse();
});

// Criterion 8: the user overview loses its SSO identities card.

it('shows the user overview without SSO identities', function (): void {
    $this->actingAs(user());
    $target = user(['name' => 'Ada']);
    organization($target, ['name' => 'Acme']);

    $this->get(route('atrium.roster.users.show', [$target->getRouteKey(), 'tab' => 'overview']))
        ->assertOk()
        ->assertViewHas('memberships')
        ->assertViewMissing('ssoIdentities')
        ->assertDontSee('unlink-sso-identity', false);
});

// Criterion 9: the config is gone.

it('has no sso or scim config, nor their table keys', function (): void {
    expect(config('roster.sso'))->toBeNull()
        ->and(config('roster.scim'))->toBeNull()
        ->and((array) config('roster.tables'))->toHaveKey('organizations')->not->toHaveKeys(REMOVED_SSO_SCIM_TABLES)
        ->and(Tables::DEFAULTS)->toHaveKey('organizations')->not->toHaveKeys(REMOVED_SSO_SCIM_TABLES);

    $config = require dirname(__DIR__, 2).'/config/roster.php';

    expect($config)->not->toHaveKeys(['sso', 'scim'])
        ->and($config['tables'])->not->toHaveKeys(REMOVED_SSO_SCIM_TABLES);
});

// Criterion 12: no source names SSO or SCIM.

it('names SSO or SCIM nowhere in src, config, database, lang, resources or workbench', function (): void {
    expect(removedSsoScimHits(['src', 'config', 'database', 'lang', 'resources', 'workbench'], REMOVED_SSO_SCIM))->toBe([]);
});

it('catches each form of the names it looks for, but not other words', function (string $text, bool $caught): void {
    expect(preg_match(REMOVED_SSO_SCIM, $text) === 1)->toBe($caught);
})->with([
    'snake_case' => ['sso_connections', true],
    'kebab-case' => ['scim-tokens', true],
    'a leading word' => ['sso is on', true],
    'camelCase' => ['ssoIdentities', true],
    'PascalCase' => ['ScimTokenModel', true],
    'an embedded PascalCase' => ['NotSsoEnforced', true],
    'upper case' => ['SSO and SCIM', true],
    'a dotted prefix' => ['roster.scim.users', true],
    'association' => ['association', false],
    'an upper-case run' => ['ASSOCIATE', false],
]);

// Criterion 11: no SSO vendor library is referenced.

it('references no SSO vendor library in tests, workbench, src, config or resources', function (): void {
    $pattern = '/'.implode('|', array_map(fn (string $name): string => preg_quote($name, '/'), ['Laravel\\Socialite', 'SocialiteProviders', 'Firebase\\JWT'])).'/';

    expect(removedSsoScimHits(
        ['tests', 'workbench', 'src', 'config', 'resources'],
        $pattern,
        ['tests/Feature/RemovedSsoScimTest.php', 'tests/Browser/Screenshots', 'tests/Browser/Traces'],
    ))->toBe([]);
});
