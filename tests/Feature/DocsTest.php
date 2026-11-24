<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Mcp\RosterServer;

const BOOST_SKILL = 'resources/boost/skills/roster-development/SKILL.md';

function readme(): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
}

function packageFile(string $file): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);
}

/**
 * The section under the first heading named $title (optionally numbered, as
 * in "### 13. Registered tabs"), up to the next heading of the same or a
 * higher level. Lines inside fenced code blocks are never headings. Null when
 * there is no such heading.
 */
function markdownSection(string $markdown, string $title): ?string
{
    $section = null;
    $level = 0;
    $fenced = false;

    foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
        if (preg_match('/^\s*(```|~~~)/', $line) === 1) {
            $fenced = ! $fenced;
        }

        $match = [];
        $heading = ! $fenced && preg_match('/^(#{1,6})\s+(.*?)\s*#*\s*$/', $line, $match) === 1;

        if ($section === null) {
            if ($heading && preg_replace('/^\d+\.\s+/', '', $match[2]) === $title) {
                $section = '';
                $level = strlen($match[1]);
            }

            continue;
        }

        if ($heading && strlen($match[1]) <= $level) {
            break;
        }

        $section .= $line."\n";
    }

    return $section;
}

/**
 * The CHANGELOG's section for $version (as in "v2.0.0"), up to the next
 * version. Empty when there is none.
 */
function changelogVersion(string $version): string
{
    $changelog = (string) file_get_contents(dirname(__DIR__, 2).'/CHANGELOG.md');

    return preg_match('/^## \['.preg_quote($version, '/').'\][^\n]*\n(.*?)(?=^## |\z)/ms', $changelog, $match) === 1 ? $match[1] : '';
}

/**
 * The CHANGELOG's v0.1.0 section, which carries the Guardian upgrade notes.
 */
function releaseChangelog(): string
{
    return changelogVersion('v0.1.0');
}

/**
 * The README's text before its first `## ` heading: the package's
 * one-paragraph description and feature list.
 */
function readmeIntro(): string
{
    $readme = readme();
    $end = strpos($readme, "\n## ");

    return $end === false ? $readme : substr($readme, 0, $end);
}

/**
 * The lines of some text that contain every one of $needles.
 *
 * @param  array<int, string>  $needles
 * @return array<int, string>
 */
function linesWith(string $text, array $needles): array
{
    return array_values(array_filter(
        preg_split('/\R/', $text) ?: [],
        function (string $line) use ($needles): bool {
            foreach ($needles as $needle) {
                if (! str_contains($line, $needle)) {
                    return false;
                }
            }

            return true;
        },
    ));
}

/**
 * The endpoints Roster v2 added for the UI Atrium v2 builds on them: URI as
 * the README's HTTP API table writes it => route name. (The organization's
 * role assignments went with the Role domain: listing them is the role
 * store's.)
 */
const V2_ENDPOINTS = [
    '/roster/invitations/{token}' => 'roster.invitations.lookup',
    '/roster/stats/users' => 'roster.stats.users',
    '/roster/stats/organizations' => 'roster.stats.organizations',
    '/roster/search/users?q=&limit=' => 'roster.search.users',
    '/roster/search/organizations?q=&limit=' => 'roster.search.organizations',
];

/**
 * What the Atrium v1 dashboard was. None of it may be documented as Roster's
 * any more; the CHANGELOG's upgrade note is where it is named.
 */
const ATRIUM_V1_TERMS = [
    'composer require refactor-circus/atrium', 'atrium.roster.', 'ScreenAccess', 'rosterCan', 'viewAtrium', 'atrium.view',
    'RosterSupportFeature', 'RosterPlugin', 'AuthorizesScreens', 'Atrium::screens()', 'x-atrium', 'atrium.disabled',
    'roster.atrium', 'rosterUsesAtrium', 'withoutAtrium', 'AtriumUnavailable', 'without-atrium', 'test:browser',
    // The v1 dashboard's URL (`/atrium`), not the Atrium v2 module's `dist/atrium/` build.
    'pennantplus', 'ResetsScreenExtensions', '`/atrium`', ' /atrium',
];

it('reads a section up to the next heading of the same or a higher level', function (): void {
    $markdown = <<<'MD'
        ## Parent
        `boot()` outside
        ### Wanted
        inside
        ```bash
        # not a heading
        ```
        #### Deeper
        still inside
        ### Next
        `Other::class` outside
        MD;

    $section = markdownSection($markdown, 'Wanted');

    expect($section)->toContain('inside')
        ->toContain('# not a heading')
        ->toContain('still inside')
        ->not->toContain('`Other::class`')
        ->not->toContain('`boot()`')
        ->and(markdownSection("### 13. Wanted\nnumbered\n## Next", 'Wanted'))->toBe("numbered\n")
        ->and(markdownSection('## Other', 'Wanted'))->toBeNull();
});

it('finds a version of the CHANGELOG and the lines holding every needle', function (): void {
    expect(changelogVersion('v9.9.9'))->toBe('')
        ->and(changelogVersion('v0.1.0'))->toContain('### Breaking')
        ->and(linesWith("| `a` | `b` |\n| `a` | `c` |\n| `b` |", ['`a`', '`c`']))->toBe(['| `a` | `c` |']);
});

it('describes Roster as a JSON API and MCP package whose optional UI is Atrium v2', function (): void {
    $intro = readmeIntro();

    expect($intro)->toContain('JSON API')
        ->toContain('MCP server')
        ->toContain('Atrium v2')
        ->toContain('optional')
        ->not->toContain('Atrium dashboard')
        ->and(markdownSection(readme(), 'Installation'))->not->toContain('refactor-circus/atrium');
});

it('drops the Atrium dashboard from the README and the Boost skill', function (string $file): void {
    $markdown = packageFile($file);

    foreach (['Atrium dashboard', 'Screen extensions', 'Registered tabs', 'MCP redirect domains'] as $title) {
        expect(markdownSection($markdown, $title))->toBeNull("{$file} still has a {$title} heading");
    }

    foreach (ATRIUM_V1_TERMS as $term) {
        expect($markdown)->not->toContain($term);
    }
})->with([
    'README' => 'README.md',
    'Boost skill' => BOOST_SKILL,
]);

it('documents the v2 endpoints in the README HTTP API table', function (): void {
    $section = (string) markdownSection(readme(), 'HTTP API and MCP');

    foreach (V2_ENDPOINTS as $uri => $name) {
        expect(linesWith($section, ['| GET |', '`'.$uri.'`', '`'.$name.'`']))->not->toBeEmpty("no GET row for {$name} at {$uri}");
    }
});

it('maps each target status of a user to its route in the README, with no status endpoint', function (): void {
    $section = markdownSection(readme(), "Changing a user's status");

    expect($section)->not->toBeNull("the README has no \"Changing a user's status\" heading");

    foreach ([
        'active' => ['roster.users.reactivate', '/roster/users/{user}/reactivate', 'reactivate-user-tool'],
        'suspended' => ['roster.users.suspend', '/roster/users/{user}/suspend', 'suspend-user-tool'],
        'deactivated' => ['roster.users.deactivate', '/roster/users/{user}/deactivate', 'deactivate-user-tool'],
    ] as $status => [$route, $uri, $tool]) {
        expect(linesWith((string) $section, ['`'.$status.'`', '`'.$route.'`', '`POST '.$uri.'`', '`'.$tool.'`']))
            ->toHaveCount(1, "no single row maps `{$status}` to {$route}");
    }

    expect($section)->toContain('roster.users.manage-status')
        ->toContain('`pending`')
        ->toContain('roster.users.approve');
});

it('documents the security model of the public invitation lookup', function (): void {
    $section = markdownSection(readme(), 'Public invitation lookup');

    expect($section)->not->toBeNull('the README has no Public invitation lookup heading');

    foreach ([
        // Public, its own middleware, and only with the API.
        'GET /roster/invitations/{token}', '`roster.invitations.lookup`', '`roster.invitations.lookup_middleware`',
        "['api', 'throttle:roster-invitation-lookup']", '`roster.routes.middleware`', '`roster.routes.enabled`',
        // Not enumerable.
        '48', 'alphanumeric', '404', 'expired', 'revoked', 'accepted', 'declined', 'deleted',
        // Rate-limited per IP.
        '`roster.rate_limit.invitation_lookups_per_minute`', '10', 'per IP', '429',
        // A minimal, uncached payload.
        '`Cache-Control: no-store`', '`email`', '`organization.name`', '`teams`', '`expires_at`', 'never',
        // The token in the URL.
        'access logs', 'accept_url',
    ] as $needle) {
        expect($section)->toContain($needle);
    }
});

it('says in the README that the HTTP API, and with it the lookup, is off by default', function (): void {
    $section = (string) markdownSection(readme(), 'HTTP API and MCP');

    expect($section)->toContain('`roster.routes.enabled` defaults to `false`')
        ->toContain("'routes' => ['enabled' => true")
        ->and(markdownSection(readme(), 'Public invitation lookup'))->toContain('only while `roster.routes.enabled` is `true`');
});

it('documents how to regenerate and consume the OpenAPI document and the client', function (): void {
    $section = markdownSection(readme(), 'OpenAPI document and TypeScript client');

    expect($section)->not->toBeNull('the README has no OpenAPI document and TypeScript client heading');

    foreach ([
        '`openapi.json`', 'OpenAPI 3.1', 'operationId', 'route name', 'composer client:generate', 'workbench:openapi',
        'npm --prefix client run generate', 'npm --prefix client run typecheck', '`client/`',
        '@refactor-circus/roster-client', 'openapi-fetch', 'createRosterClient', 'baseUrl', 'credentials',
        '`paths`', '`components`', 'sha256', 'OpenApiTest', '#v2.0.0&path:/client', 'Atrium v2',
    ] as $needle) {
        expect($section)->toContain($needle);
    }
});

it('writes the v2.0.0 upgrade note in the Breaking section of the CHANGELOG', function (): void {
    $breaking = markdownSection(changelogVersion('v2.0.0'), 'Breaking');

    expect($breaking)->not->toBeNull('the CHANGELOG has no v2.0.0 Breaking heading');

    foreach ([
        // Where the dashboard went.
        'Atrium v2', 'https://github.com/Refactor-Circus/Atrium',
        // Removed classes.
        'RefactorCircus\Roster\Atrium', 'RosterPlugin', 'AtriumServiceProvider', 'ScreenAccess', 'Badges',
        'AuthorizesScreens', 'UiController', 'RosterSupportFeature',
        // Routes, views and config.
        'atrium.roster.*', 'resources/views/ui', 'roster-views', '`roster.atrium`', '`@rosterCan`', 'rosterUsesAtrium',
        // Access.
        '`viewAtrium`', '`atrium.view`', '`atrium.*`', 'roster:sync-permissions',
        // Dependencies.
        'refactor-circus/pennantplus', 'refactor-circus/atrium',
        // What replaces the Atrium links.
        '`roster.users.approvals.review_url`', '`roster.impersonation.return_to`',
    ] as $needle) {
        expect($breaking)->toContain($needle);
    }
});

it('lists what v2.0.0 added in the CHANGELOG', function (): void {
    $added = markdownSection(changelogVersion('v2.0.0'), 'Added');

    expect($added)->not->toBeNull('the CHANGELOG has no v2.0.0 Added heading');

    foreach ([
        ...array_map(fn (string $name): string => '`'.$name.'`', array_values(V2_ENDPOINTS)),
        '`show-user-stats-tool`', '`show-organization-stats-tool`',
        '`roster.invitations.lookup_middleware`', '`roster.rate_limit.invitation_lookups_per_minute`',
        '`roster.users.approvals.review_url`', '`openapi.json`', '`@refactor-circus/roster-client`',
        '`composer client:generate`',
    ] as $needle) {
        expect($added)->toContain($needle);
    }
});

it('keeps the agent guides free of Atrium architecture and documents the v2 API', function (string $file): void {
    $guide = packageFile($file);

    // Atrium may only be named as Atrium v2, the optional UI on the API, or
    // as Roster's own front-end module for it (`atrium/`, `dist/atrium/`,
    // `extra.atrium`).
    $atriumLines = array_values(array_filter(
        preg_grep('/atrium/i', preg_split('/\R/', $guide) ?: []) ?: [],
        fn (string $line): bool => ! str_contains($line, 'Atrium v2') && ! preg_match('#`(dist/)?atrium/`|`extra\.atrium`#', $line),
    ));

    expect($atriumLines)->toBe([]);

    foreach (ATRIUM_V1_TERMS as $term) {
        expect($guide)->not->toContain($term);
    }

    foreach ([...array_values(V2_ENDPOINTS), 'composer client:generate', 'openapi.json', 'OpenApiTest'] as $needle) {
        expect($guide)->toContain($needle);
    }
})->with([
    'AGENTS.md (CLAUDE.md links to it)' => 'AGENTS.md',
    'Boost skill' => BOOST_SKILL,
]);

it('updates the local standards for a package without a UI', function (): void {
    $root = dirname(__DIR__, 2).'/docs/agent/standards';

    expect(is_file($root.'/roster/atrium-views.md'))->toBeFalse('roster/atrium-views still exists');

    foreach (['roster/action-surfaces.md', 'roster/optional-surfaces.md', 'roster/testbench-environment.md', 'http/authorization.md'] as $file) {
        $standard = (string) file_get_contents($root.'/'.$file);

        expect(preg_grep('/atrium|ScreenAccess|rosterCan|\bui\b|dashboard/i', preg_split('/\R/', $standard) ?: []) ?: [])
            ->toBe([], "{$file} still describes the Atrium UI");
    }

    expect((string) file_get_contents($root.'/roster/action-surfaces.md'))->toContain('HTTP API')->toContain('MCP')
        ->and((string) file_get_contents($root.'/roster/optional-surfaces.md'))->toContain('roster.routes.enabled')
        ->toContain('roster.invitations.lookup_middleware')
        ->and((string) file_get_contents($root.'/roster/testbench-environment.md'))->toContain('McpServiceProvider`, `ImpexServiceProvider`, then `RosterServiceProvider`');
});

it('logs the v2 deviations and resolves the plain-views row in the standards feedback', function (): void {
    $feedback = packageFile('docs/agent/standards-feedback.md');

    expect(linesWith($feedback, ['2026-11-23-0943-roster-v2-api-and-mcp-only', 'http/authorization', 'LookUpInvitationRequest']))->toHaveCount(1)
        ->and(linesWith($feedback, ['2026-11-23-0943-roster-v2-api-and-mcp-only', 'actions/actions-events', 'InvitationLookedUpActionEvent']))->toHaveCount(1)
        ->and(linesWith($feedback, ['2026-10-30', 'roster/atrium-views', 'Resolved 2026-11-23']))->toHaveCount(1);
});

/**
 * The bold title of the CHANGELOG entry, and the README section, that tell
 * apps SSO and SCIM moved to refactor-circus/guardian.
 */
const GUARDIAN_ENTRY = 'SSO and SCIM moved to refactor-circus/guardian';

const GUARDIAN_SECTION = 'SSO and SCIM (refactor-circus/guardian)';

/**
 * The fail-open warning both open with (D-017).
 */
const FAIL_OPEN_WARNING = '**Without Guardian, organizations that enforce SSO accept password sign-in.**';

/**
 * The criterion 12 regex, verbatim: any SSO or SCIM token.
 */
const DOCS_SSO_SCIM = '/(?<![A-Za-z])(?:sso|scim)|Sso|Scim|(?<![A-Z])(?:SSO|SCIM)(?![A-Z])/';

/**
 * The body of the top-level bullet in $markdown whose bold title starts with
 * $title: every line after the title line, up to the next top-level bullet
 * or heading. Null when there is no such bullet.
 */
function changelogEntry(string $markdown, string $title): ?string
{
    $entry = null;

    foreach (preg_split('/\R/', $markdown) ?: [] as $line) {
        if ($entry === null) {
            if (str_starts_with($line, '- **'.$title)) {
                $entry = '';
            }

            continue;
        }

        if (str_starts_with($line, '- ') || str_starts_with($line, '#')) {
            break;
        }

        $entry .= $line."\n";
    }

    return $entry;
}

/**
 * The first non-blank line of some text, trimmed.
 */
function firstLine(string $text): string
{
    foreach (preg_split('/\R/', $text) ?: [] as $line) {
        if (trim($line) !== '') {
            return trim($line);
        }
    }

    return '';
}

/**
 * The numbered list items of some text, in order, as [number, text] pairs.
 *
 * @return array<int, array{0: int, 1: string}>
 */
function numberedSteps(string $text): array
{
    preg_match_all('/^\s*(\d+)\.\s+(.*)$/m', $text, $matches, PREG_SET_ORDER);

    return array_map(fn (array $match): array => [(int) $match[1], $match[2]], $matches);
}

/**
 * A markdown text without the section under the heading $title.
 */
function withoutSection(string $markdown, string $title): string
{
    $section = markdownSection($markdown, $title);

    return $section === null || $section === '' ? $markdown : str_replace($section, '', $markdown);
}

it('finds a changelog entry by its bold title, up to the next top-level bullet', function (): void {
    $markdown = <<<'MD'
        - **Other.** Not this one.
        - **Moved away.**

          **Warning.**

          1. First
          2. **Second**
        - **Next.** Outside.
        MD;

    $entry = (string) changelogEntry($markdown, 'Moved away');

    expect(firstLine($entry))->toBe('**Warning.**')
        ->and(numberedSteps($entry))->toBe([[1, 'First'], [2, '**Second**']])
        ->and($entry)->not->toContain('Outside')->not->toContain('Not this one')
        ->and(changelogEntry($markdown, 'Missing'))->toBeNull()
        ->and(withoutSection("## A\nkeep\n## B\ndrop\n## C\nkeep too", 'B'))->not->toContain('drop')->toContain('keep too');
});

it('writes the Guardian upgrade entry in the v0.1.0 Breaking section of the CHANGELOG', function (): void {
    $entry = changelogEntry((string) markdownSection(releaseChangelog(), 'Breaking'), GUARDIAN_ENTRY);

    expect($entry)->not->toBeNull('the v0.1.0 Breaking section has no "'.GUARDIAN_ENTRY.'" entry')
        ->and(firstLine((string) $entry))->toStartWith(FAIL_OPEN_WARNING);

    foreach ([
        'refactor-circus/guardian', 'guardian.sso', '?tab=sso', '?tab=scim', 'roster.sso.', 'create_roster_sso_tables',
        'guardian:upgrade-from-roster', 'NoExternalSignIn',
        // What was removed.
        'NotSsoEnforced', 'MembershipSource', 'laravel/socialite', 'roster.tables',
        // The behaviour without Guardian.
        '404', 'roster:sync-permissions', '`scim`',
    ] as $needle) {
        expect($entry)->toContain($needle);
    }
});

it('gives the six upgrade steps in order, with step 2 bold', function (): void {
    $steps = numberedSteps((string) changelogEntry((string) markdownSection(releaseChangelog(), 'Breaking'), GUARDIAN_ENTRY));

    expect(array_column($steps, 0))->toBe([1, 2, 3, 4, 5, 6]);

    [$install, $published, $migrate, $config, $names, $rule] = array_column($steps, 1);

    expect($install)->toContain('refactor-circus/guardian')->toContain('same deploy')
        ->and($published)->toStartWith('**Before running any migrate command**')
        ->toContain('create_roster_sso_tables')->toContain('create_roster_scim_tables')->toContain('down()')
        ->and($migrate)->toContain('roster:sync-permissions')->toContain('guardian.sso.*')
        ->toContain('**If you installed Guardian before this Roster version**')->toContain('php artisan guardian:upgrade-from-roster')
        ->and($config)->toContain('roster.sso.*')->toContain('guardian.*')->toContain('scim/v2')
        ->and($names)->toContain('RefactorCircus\Roster\Domains\Sso')->toContain('@rosterCan')
        ->and($rule)->toContain('NotSsoEnforced')->toContain('RefactorCircus\Roster\Support\Rules\NoExternalSignIn')->toContain('external_sign_in_required');
});

it('replaces the README single sign-on and SCIM sections with one Guardian section', function (): void {
    $readme = readme();
    $section = markdownSection($readme, GUARDIAN_SECTION);

    expect($section)->not->toBeNull('the README has no "'.GUARDIAN_SECTION.'" heading')
        ->and(firstLine((string) $section))->toStartWith(FAIL_OPEN_WARNING)
        ->and($section)->toContain('https://github.com/Refactor-Circus/Guardian')
        ->toContain('guardian:upgrade-from-roster')
        ->toContain('create_roster_sso_tables')
        ->toContain('NoExternalSignIn')
        ->and(markdownSection($readme, 'Single sign-on'))->toBeNull('the README still has a Single sign-on heading')
        ->and(markdownSection($readme, 'Requiring SSO'))->toBeNull('the README still has a Requiring SSO heading')
        ->and(markdownSection($readme, 'SCIM provisioning'))->toBeNull('the README still has a SCIM provisioning heading');
});

it('points the Boost skill at Guardian for single sign-on and provisioning', function (): void {
    $section = markdownSection(packageFile(BOOST_SKILL), 'Single sign-on and provisioning');

    expect($section)->not->toBeNull('the Boost skill has no Single sign-on and provisioning heading')
        ->toContain('refactor-circus/guardian')
        ->toContain('guardian:upgrade-from-roster')
        ->toContain('NoExternalSignIn');
});

it('names no removed SSO class outside the Guardian section', function (string $file): void {
    $outside = withoutSection(packageFile($file), GUARDIAN_SECTION);

    expect($outside)->not->toContain('NotSsoEnforced')
        ->not->toContain('SsoConnectionModel')
        ->not->toContain('Domains\Sso');
})->with([
    'README' => 'README.md',
    'Boost skill' => BOOST_SKILL,
]);

it('documents no removed SSO or SCIM surface outside the Guardian section of the README', function (): void {
    $outside = withoutSection(readme(), GUARDIAN_SECTION);

    foreach (['sso-connections', 'sso-identit', 'scim-tokens', 'scim_token', 'roster.sso', 'roster.scim', 'SsoConnection', 'ScimToken', 'scim/v2', 'scim.php'] as $needle) {
        expect($outside)->not->toContain($needle);
    }
});

it('no longer cites Roster SSO or SCIM as users of the extension seams', function (string $file): void {
    $section = (string) markdownSection(packageFile($file), 'Extending Roster');

    expect(array_values(preg_grep(DOCS_SSO_SCIM, preg_split('/\R/', $section) ?: []) ?: []))->toBe([])
        ->and($section)->toContain('NoExternalSignIn')
        ->toContain('a registered sign-in requirement');
})->with([
    'README' => 'README.md',
    'Boost skill' => BOOST_SKILL,
]);

it('keeps the agent guides free of SSO and SCIM', function (string $file): void {
    $guide = packageFile($file);

    expect(array_values(preg_grep(DOCS_SSO_SCIM, preg_split('/\R/', $guide) ?: []) ?: []))->toBe([])
        ->and($guide)->not->toContain('scim.php');
})->with(['CLAUDE.md', 'AGENTS.md']);

/**
 * The fenced code blocks of a markdown text.
 *
 * @return array<int, string>
 */
function codeBlocks(string $markdown): array
{
    preg_match_all('/^\s*(```|~~~)[^\n]*\n(.*?)^\s*\1\s*$/ms', $markdown, $matches);

    return $matches[2];
}

/**
 * Every `RefactorCircus\Roster\...` (or Keystone) class name in some code,
 * group imports (`use A\{B, C};`) expanded.
 *
 * @return array<int, string>
 */
function refactorCircusNames(string $code): array
{
    $code = (string) preg_replace_callback(
        '/(RefactorCircus\\\\[A-Za-z0-9_\\\\]+)\\\\\{([^}]*)\}/',
        fn (array $match): string => implode(' ', array_map(
            fn (string $name): string => $match[1].'\\'.trim(preg_replace('/\s+as\s+\w+$/i', '', trim($name)) ?? ''),
            explode(',', $match[2]),
        )),
        $code,
    );

    preg_match_all('/\\\\?(RefactorCircus\\\\(?:Roster|Keystone)\\\\[A-Za-z0-9_\\\\]*[A-Za-z0-9_])/', $code, $matches);

    return array_values(array_unique($matches[1]));
}

it('finds the code blocks and the class names in them', function (): void {
    $markdown = <<<'MD'
        Outside `RefactorCircus\Roster\Outside`.
        ```php
        use RefactorCircus\Roster\Support\{Users, Slugs as RosterSlugs};
        app(\RefactorCircus\Roster\Support\Authorization\Resources::class);
        ```
        MD;

    $blocks = codeBlocks($markdown);

    expect($blocks)->toHaveCount(1)
        ->and(refactorCircusNames($blocks[0]))->toBe([
            'RefactorCircus\Roster\Support\Users',
            'RefactorCircus\Roster\Support\Slugs',
            'RefactorCircus\Roster\Support\Authorization\Resources',
        ]);
});

it('documents every extension seam in the README and the Boost skill', function (string $file): void {
    $section = markdownSection((string) file_get_contents(dirname(__DIR__, 2).'/'.$file), 'Extending Roster');

    expect($section)->not->toBeNull("{$file} has no Extending Roster heading")
        ->and($section)->toContain('Resources::class')
        ->toContain('Abilities::class')
        ->toContain('MembershipSources::class')
        ->toContain('SignInRequirement')
        ->toContain('requireSignIn(')
        ->toContain('externalSignInFor(')
        ->toContain('provisionedStatus(')
        ->toContain('AuditHooks')
        // Audit context is Keystone's, filtered to Roster's entries and never
        // breaking them.
        ->toContain("?->key !== 'roster'")
        ->toContain('catch (Throwable $e)')
        ->toContain('report($e)')
        ->toContain('`roster.*`')
        ->toContain('roster.impersonation.blocked');
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/roster-development/SKILL.md',
]);

it('names only classes that exist in the Extending Roster examples', function (string $file): void {
    $section = (string) markdownSection((string) file_get_contents(dirname(__DIR__, 2).'/'.$file), 'Extending Roster');
    $names = refactorCircusNames(implode("\n", codeBlocks($section)));

    $missing = array_values(array_filter(
        $names,
        fn (string $name): bool => ! class_exists($name) && ! interface_exists($name) && ! trait_exists($name) && ! enum_exists($name),
    ));

    expect($names)->toContain(
        'RefactorCircus\Roster\Support\Authorization\Resources',
        'RefactorCircus\Keystone\Authorization\Abilities',
        'RefactorCircus\Roster\Domains\Organization\Services\MembershipSources',
        'RefactorCircus\Roster\Contracts\SignInRequirement',
        'RefactorCircus\Roster\Data\ExternalSignIn',
        'RefactorCircus\Keystone\Audit\AuditHooks',
    )->and($missing)->toBe([]);
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/roster-development/SKILL.md',
]);

it('no longer documents Roster::ssoRequiredFor()', function (string $file): void {
    expect((string) file_get_contents(dirname(__DIR__, 2).'/'.$file))->not->toContain('ssoRequiredFor');
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/roster-development/SKILL.md',
]);

it('documents every API route in the README', function (): void {
    $readme = readme();

    $missing = collect(Route::getRoutes()->getRoutesByName())
        ->keys()
        ->filter(fn (string $name): bool => str_starts_with($name, 'roster.') && ! str_starts_with($name, 'roster.invitations.page') && $name !== 'roster.invitations.show' && ! str_starts_with($name, 'roster.impersonation.') && ! str_starts_with($name, 'roster.sso.') && ! str_starts_with($name, 'roster.scim.'))
        ->reject(function (string $name) use ($readme): bool {
            // The table lists `roster.x.index`, `.store` together; match the full
            // name or its last segment after a listed sibling.
            $parts = explode('.', $name);
            $last = array_pop($parts);

            return str_contains($readme, '`'.$name.'`') || str_contains($readme, '`.'.$last.'`') && str_contains($readme, '`'.implode('.', $parts).'.');
        })
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

it('documents every MCP tool in the README', function (): void {
    $readme = readme();

    $missing = collect(RosterServer::TOOLS)
        ->map(fn (string $tool): string => app($tool)->name())
        ->reject(fn (string $name): bool => str_contains($readme, '`'.$name.'`'))
        ->values()
        ->all();

    expect($missing)->toBe([]);
});

/**
 * What Roster v2 no longer has, which no current doc may name (criterion 45).
 */
const REMOVED_AUTHORIZATION_TERMS = ['PermissionCatalogue', 'roster:sync-permissions', 'permission_cache', 'rosterRoleAssignments'];

it('documents authorization through Keystone\'s contract in the README Roles and permissions section', function (): void {
    $section = markdownSection(readme(), 'Roles and permissions');

    expect($section)->not->toBeNull('the README has no Roles and permissions heading');

    foreach ([
        // The contract and the declared abilities.
        'refactor-circus/keystone', '`Permissions`', '`Roles`', 'Abilities::define()',
        // The Gate fallback.
        'GatePermissions', "Gate::define('roster.users.view'", '`super-admin`',
        // A role store brings roles back.
        'refactor-circus/guardian',
        // The owner rule and the global-only abilities (D-022: not `roster.organizations.sync`).
        'owner', 'OrganizationModel::GLOBAL_ONLY_ABILITIES', '`roster.users.*`', '`roster.organizations.create`',
    ] as $needle) {
        expect($section)->toContain($needle);
    }

    expect($section)->not->toContain('roster:grant-super-admin')
        ->not->toContain('roster.super_admins')
        ->not->toContain('| `lead` |')
        ->and(linesWith((string) $section, ['global-only', 'roster.organizations.sync']))->toBe([]);
});

it('names nothing removed with the Role and Permission domains in the docs and guides', function (string $file): void {
    foreach (REMOVED_AUTHORIZATION_TERMS as $term) {
        expect(packageFile($file))->not->toContain($term);
    }
})->with(function (): array {
    $root = dirname(__DIR__, 2);
    $files = ['README.md', BOOST_SKILL, 'CLAUDE.md', 'AGENTS.md'];

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root.'/docs/agent/standards', FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file instanceof SplFileInfo && $file->isFile()) {
            $files[] = substr($file->getPathname(), strlen($root) + 1);
        }
    }

    sort($files);

    return array_combine($files, array_map(fn (string $file): array => [$file], $files));
});

it('names what v2.0.0 removed with the Role and Permission domains in the CHANGELOG, with the Guardian upgrade note', function (): void {
    $breaking = (string) markdownSection(changelogVersion('v2.0.0'), 'Breaking');

    foreach ([
        // Routes.
        'roster.roles.*', 'roster.permissions.*', 'roster.users.roles.*', 'roster.users.permissions', 'roster.organizations.role-assignments.index',
        // MCP tools.
        'list-permissions-tool', 'create-permission-tool', 'update-permission-tool', 'delete-permission-tool',
        'list-roles-tool', 'show-role-tool', 'create-role-tool', 'update-role-tool', 'delete-role-tool',
        'list-role-assignments-tool', 'assign-role-tool', 'revoke-role-tool', 'list-user-permissions-tool',
        // Commands.
        'roster:sync-permissions', 'roster:grant-super-admin',
        // Models, config and classes.
        'RoleModel', 'PermissionModel', 'RoleAssignmentModel', '`roster.permission_cache`', '`roster.super_admins`',
        'PermissionCatalogue', 'BuiltInRoles', 'Permissions::GLOBAL_ONLY_PREFIXES', 'OrganizationModel::GLOBAL_ONLY_ABILITIES',
        'rosterRoleAssignments()', 'RefactorCircus\Roster\Support\Authorization\Authorizer', 'RefactorCircus\Roster\Support\Authorization\Resources',
        // The upgrade note.
        '`refactor-circus/guardian` `^2.0`', 'migrations', 'roster_roles', 'roster_role_assignments', 'drop', 'Gate::define(',
    ] as $needle) {
        expect($breaking)->toContain($needle);
    }
});
