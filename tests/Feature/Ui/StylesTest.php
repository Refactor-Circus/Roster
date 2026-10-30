<?php

declare(strict_types=1);

use RefactorCircus\Atrium\Testing\AtriumStyles;

/**
 * The two views Roster renders without Atrium, which style themselves
 * inline; recorded in docs/agent/standards-feedback.md.
 */
const ROSTER_PLAIN_VIEWS = [
    'invitations/show.blade.php',
    'partials/impersonation-banner-plain.blade.php',
];

/**
 * Roster ships no stylesheet: its views use Atrium's components and the
 * utilities Atrium compiles, so a class missing from Atrium's stylesheet
 * would silently do nothing.
 */
it('uses only atrium styles', function (): void {
    $views = dirname(__DIR__, 3).'/resources/views';

    $plain = fn (string $path): bool => in_array(str_replace(DIRECTORY_SEPARATOR, '/', $path), ROSTER_PLAIN_VIEWS, true);

    expect(array_filter(AtriumStyles::missingClasses($views), fn (string $path): bool => ! $plain($path)))->toBe([])
        ->and(array_values(array_filter(AtriumStyles::inlineStyles($views), fn (string $path): bool => ! $plain($path))))->toBe([]);
});

it('excludes only the two plain views, which exist', function (): void {
    $views = dirname(__DIR__, 3).'/resources/views';

    foreach (ROSTER_PLAIN_VIEWS as $view) {
        expect($views.'/'.$view)->toBeFile()
            ->and((string) file_get_contents($views.'/'.$view))->not->toContain('x-atrium');
    }
});
