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

it('reads a section up to the next heading of the same or a higher level', function (): void {
    $markdown = <<<'MD'
        ## Atrium
        `boot()` outside
        ### Registered tabs
        inside
        ```bash
        # not a heading
        ```
        #### Deeper
        still inside
        ### Next
        `Tabs::class` outside
        MD;

    $section = markdownSection($markdown, 'Registered tabs');

    expect($section)->toContain('inside')
        ->toContain('# not a heading')
        ->toContain('still inside')
        ->not->toContain('`Tabs::class`')
        ->not->toContain('`boot()`')
        ->and(markdownSection("### 13. Registered tabs\nnumbered\n## Next", 'Registered tabs'))->toBe("numbered\n")
        ->and(markdownSection('## Other', 'Registered tabs'))->toBeNull();
});

it('documents registered tabs in the README and the Boost skill', function (string $file): void {
    $section = markdownSection((string) file_get_contents(dirname(__DIR__, 2).'/'.$file), 'Registered tabs');

    expect($section)->not->toBeNull("{$file} has no Registered tabs heading")
        ->and($section)->toContain('Tabs::class')
        ->toContain('vendor.key')
        ->toContain('boot()');
})->with([
    'README' => 'README.md',
    'Boost skill' => 'resources/boost/skills/roster-development/SKILL.md',
]);

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
        use RefactorCircus\Roster\Atrium\{TabDefinition, Tabs as RosterTabs};
        app(\RefactorCircus\Roster\Domains\Permission\Services\Resources::class);
        ```
        MD;

    $blocks = codeBlocks($markdown);

    expect($blocks)->toHaveCount(1)
        ->and(refactorCircusNames($blocks[0]))->toBe([
            'RefactorCircus\Roster\Atrium\TabDefinition',
            'RefactorCircus\Roster\Atrium\Tabs',
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
