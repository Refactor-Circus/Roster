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
 * The CHANGELOG's Unreleased section, up to the first released version.
 */
function unreleasedChangelog(): string
{
    $changelog = (string) file_get_contents(dirname(__DIR__, 2).'/CHANGELOG.md');

    return preg_match('/^## \[Unreleased\][^\n]*\n(.*?)(?=^## |\z)/ms', $changelog, $match) === 1 ? $match[1] : '';
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
    $builtInTabs = ['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'settings', 'overview'];
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

it('writes the upgrade note in the Unreleased Breaking section of the CHANGELOG', function (): void {
    $breaking = markdownSection(unreleasedChangelog(), 'Breaking');

    expect($breaking)->not->toBeNull('the Unreleased section has no Breaking heading')
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
