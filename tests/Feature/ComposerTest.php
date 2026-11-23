<?php

declare(strict_types=1);

/**
 * Roster requires only Keystone of the suite: Cortex and Keen are
 * suggested.
 *
 * @return array<string, mixed>
 */
function rosterComposer(): array
{
    /** @var array<string, mixed> $composer */
    $composer = json_decode((string) file_get_contents(dirname(__DIR__, 2).'/composer.json'), true, flags: JSON_THROW_ON_ERROR);

    return $composer;
}

it('requires only Keystone of the suite packages', function (): void {
    $require = array_keys((array) rosterComposer()['require']);

    expect(array_values(array_filter($require, fn (string $package): bool => str_starts_with($package, 'refactor-circus/'))))
        ->toBe(['refactor-circus/keystone'])
        ->and($require)->toContain('php', 'refactor-circus/keystone', 'laravel/framework', 'laravel/mcp');
});

it('neither requires, suggests nor conflicts with Atrium', function (): void {
    $composer = rosterComposer();

    expect((array) $composer['require-dev'])->not->toHaveKey('refactor-circus/atrium')
        ->and((array) $composer['suggest'])->not->toHaveKey('refactor-circus/atrium')
        ->and($composer)->not->toHaveKey('conflict');
});

it('has no Atrium VCS repository', function (): void {
    $urls = array_column((array) rosterComposer()['repositories'], 'url');

    expect($urls)->not->toContain('https://github.com/Refactor-Circus/Atrium.git');
});

it('suggests Cortex and Keen', function (): void {
    expect(rosterComposer()['suggest'])
        ->toHaveKey('refactor-circus/cortex', 'Lets Cortex agents use the Roster tools (dev-main).')
        ->toHaveKey('refactor-circus/keen', "Records Roster's changes in the suite-wide audit log (dev-main).");
});

it('suggests Guardian for single sign-on and SCIM, and no SSO vendor library', function (): void {
    $suggest = (array) rosterComposer()['suggest'];

    expect($suggest)
        ->toHaveKey('refactor-circus/guardian', "Single sign-on (OIDC, SAML, Entra ID) and SCIM provisioning for Roster's organizations (dev-main).")
        ->not->toHaveKeys(['laravel/socialite', 'socialiteproviders/saml2', 'firebase/php-jwt', 'socialiteproviders/microsoft-azure']);
});

it('no longer needs the SSO vendor libraries for its own tests', function (): void {
    expect((array) rosterComposer()['require-dev'])
        ->not->toHaveKeys(['laravel/socialite', 'socialiteproviders/saml2', 'firebase/php-jwt', 'socialiteproviders/microsoft-azure']);
});

it('declares no Atrium plugin', function (): void {
    expect((array) rosterComposer()['extra'])->toHaveKey('laravel')->not->toHaveKey('atrium');
});

it('neither requires nor suggests PennantPlus', function (): void {
    expect((array) rosterComposer()['require'])->not->toHaveKey('refactor-circus/pennantplus')
        ->and((array) rosterComposer()['require-dev'])->not->toHaveKey('refactor-circus/pennantplus')
        ->and((array) rosterComposer()['suggest'])->not->toHaveKey('refactor-circus/pennantplus');
});

it('has no browser test tooling', function (): void {
    $composer = rosterComposer();
    $root = dirname(__DIR__, 2);

    expect((array) $composer['require-dev'])->not->toHaveKey('pestphp/pest-plugin-browser')
        ->and((array) $composer['scripts'])->not->toHaveKey('test:browser')
        ->and((string) json_encode($composer['scripts']))->not->toContain('--exclude-testsuite=Browser')
        ->and((string) file_get_contents($root.'/phpunit.xml.dist'))->not->toContain('tests/Browser')
        ->and(is_file($root.'/package.json'))->toBeFalse()
        ->and(is_file($root.'/package-lock.json'))->toBeFalse()
        ->and(is_file($root.'/.github/workflows/browser-tests.yml'))->toBeFalse()
        ->and((string) file_get_contents($root.'/ring-master.yml'))->not->toContain('browser_test');
});
