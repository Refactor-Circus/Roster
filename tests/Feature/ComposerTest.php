<?php

declare(strict_types=1);

/**
 * Roster requires only Keystone of the suite: Atrium, Cortex and Keen are
 * suggested, and an Atrium older than the screen registry is refused.
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

it('keeps Atrium as a dev dependency', function (): void {
    expect(rosterComposer()['require-dev'])->toHaveKey('refactor-circus/atrium', '^0.1');
});

it('suggests Atrium, Cortex and Keen', function (): void {
    expect(rosterComposer()['suggest'])
        ->toHaveKey('refactor-circus/atrium', "The Atrium dashboard: Roster's admin screens, widgets and search (dev-main).")
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

it('refuses an Atrium older than the screen registry', function (): void {
    expect(rosterComposer()['conflict'] ?? null)->toBe(['refactor-circus/atrium' => '<0.1.0']);
});

it('still declares its Atrium plugin', function (): void {
    expect(rosterComposer()['extra']['atrium']['plugins'] ?? null)->toBe(['RefactorCircus\\Roster\\Atrium\\RosterPlugin']);
});
