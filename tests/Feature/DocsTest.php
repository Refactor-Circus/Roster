<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use RefactorCircus\Roster\Mcp\RosterServer;

function readme(): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/README.md');
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
 * The CHANGELOG's v0.1.0 section, which carries these upgrade notes, up to
 * the next version.
 */
function releaseChangelog(): string
{
    $changelog = (string) file_get_contents(dirname(__DIR__, 2).'/CHANGELOG.md');

    return preg_match('/^## \[v0\.1\.0\][^\n]*\n(.*?)(?=^## |\z)/ms', $changelog, $match) === 1 ? $match[1] : '';
}

it('reads a section up to the next heading of the same or a higher level', function (): void {
    $markdown = <<<'MD'
        ## Atrium
        `boot()` outside
        ### Screen extensions
        inside
        ```bash
        # not a heading
        ```
        #### Deeper
        still inside
        ### Next
        `Screen::class` outside
        MD;

    $section = markdownSection($markdown, 'Screen extensions');

    expect($section)->toContain('inside')
        ->toContain('# not a heading')
        ->toContain('still inside')
        ->not->toContain('`Screen::class`')
        ->not->toContain('`boot()`')
        ->and(markdownSection("### 13. Screen extensions\nnumbered\n## Next", 'Screen extensions'))->toBe("numbered\n")
        ->and(markdownSection('## Other', 'Screen extensions'))->toBeNull();
});

it('documents screen extensions in the README and the Boost skill', function (string $file): void {
    $markdown = (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);
    $section = markdownSection($markdown, 'Screen extensions');

    expect($section)->not->toBeNull("{$file} has no Screen extensions heading")
        ->and(markdownSection($markdown, 'Registered tabs'))->toBeNull("{$file} still has a Registered tabs heading");

    $screens = [
        'roster.users', 'roster.user-create', 'roster.user', 'roster.organizations', 'roster.organization-create',
        'roster.organization', 'roster.team', 'roster.roles', 'roster.role', 'roster.permissions',
        'roster.impersonations', 'roster.transfers', 'roster.transfer', 'roster.invitation',
    ];
    $builtInTabs = ['members', 'teams', 'invitations', 'roles', 'settings', 'overview'];
    $slots = ['header.actions', 'before', 'after', 'aside', 'row.actions'];
    $records = ['OrganizationModel', 'TeamModel', 'roster.users.model', 'RoleModel', 'ImpersonationModel', 'TransferModel', 'InvitationModel'];

    foreach ([...$screens, ...$builtInTabs, ...$slots, ...$records] as $name) {
        expect($section)->toContain('`'.$name.'`');
    }

    expect($section)->toContain('ScreenAccess::allowsOn')
        ->toContain('->organization')
        ->toContain('->requester')
        ->toContain('Atrium::screens()')
        ->toContain("->tab('roster.organization'")
        ->toContain("->section('roster.user', 'aside'")
        ->toContain('Screen::standard()')
        ->toContain('vendor.key')
        ->toContain('boot()')
        ->toContain('Atrium');
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/roster-development/SKILL.md',
]);

it('says in the README that Atrium is optional and drops the MCP redirect domains', function (): void {
    $readme = readme();

    expect(markdownSection($readme, 'Installation'))->toContain('composer require refactor-circus/atrium')
        ->toContain('optional')
        ->and(markdownSection($readme, 'MCP redirect domains'))->toBeNull()
        ->and(markdownSection($readme, 'Testing'))->toContain('tests/Modes/AtriumUnavailable')
        ->toContain('composer remove --dev --no-update refactor-circus/atrium');
});

it('writes the upgrade note in the v0.1.0 Breaking section of the CHANGELOG', function (): void {
    $breaking = markdownSection(releaseChangelog(), 'Breaking');

    expect($breaking)->not->toBeNull('the v0.1.0 section has no Breaking heading')
        ->toContain('Roster no longer requires `refactor-circus/atrium`; require it to keep the dashboard')
        ->toContain('atrium.roster.')
        ->toContain('Tabs')
        ->toContain('?tab=activity')
        ->toContain('?tab=mcp')
        ->toContain('refactor-circus/atrium')
        ->toContain('conflict')
        ->toContain('<0.1.0')
        ->toContain('roster-views');
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

const BOOST_SKILL = 'resources/boost/skills/roster-development/SKILL.md';

function packageFile(string $file): string
{
    return (string) file_get_contents(dirname(__DIR__, 2).'/'.$file);
}

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
        ->toContain('roster:sync-permissions')
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

it('lists no sso or scim tab', function (string $file, string $title): void {
    $section = markdownSection(packageFile($file), $title);

    expect($section)->not->toBeNull("{$file} has no {$title} heading")
        ->and(preg_match('/`(?:sso|scim)`|\*\*(?:SSO|SCIM)\*\* tab/', (string) $section))->toBe(0);
})->with([
    'README dashboard' => ['README.md', 'Atrium dashboard'],
    'README screen extensions' => ['README.md', 'Screen extensions'],
    'Boost skill screen extensions' => [BOOST_SKILL, 'Screen extensions'],
]);

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
 * The README's promise about built-in roles and registered permissions.
 */
const ROLE_GRANT_SENTENCE = 'Existing built-in roles never gain a newly registered permission automatically';

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
        use RefactorCircus\Roster\Atrium\{ScreenAccess, Badges as RosterBadges};
        app(\RefactorCircus\Roster\Domains\Permission\Services\Resources::class);
        ```
        MD;

    $blocks = codeBlocks($markdown);

    expect($blocks)->toHaveCount(1)
        ->and(refactorCircusNames($blocks[0]))->toBe([
            'RefactorCircus\Roster\Atrium\ScreenAccess',
            'RefactorCircus\Roster\Atrium\Badges',
            'RefactorCircus\Roster\Domains\Permission\Services\Resources',
        ]);
});

it('documents every extension seam in the README and the Boost skill', function (string $file): void {
    $section = markdownSection((string) file_get_contents(dirname(__DIR__, 2).'/'.$file), 'Extending Roster');

    expect($section)->not->toBeNull("{$file} has no Extending Roster heading")
        ->and($section)->toContain('Resources::class')
        ->toContain('PermissionCatalogue::class')
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
        ->toContain('roster.impersonation.blocked')
        ->toContain('roster:sync-permissions')
        ->toContain(ROLE_GRANT_SENTENCE);
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
        'RefactorCircus\Roster\Domains\Permission\Services\Resources',
        'RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue',
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
