<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests;

use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Testing\FakePermissions;
use RefactorCircus\Keystone\Testing\FakeRoles;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Support\Authorization\RosterAbilities;

/**
 * Roster with authorization on, as shipped, and a role store bound: Keystone's
 * `FakePermissions` and `FakeRoles`, standing in for any role store.
 *
 * The fake holds the default roles a role store seeds from Roster's declared
 * abilities: a global `super-admin`, and `admin` and `member` held in an
 * organization, each granted what `RosterAbilities::grantTo()` gives it.
 * Tests grant them with `grant()`.
 */
abstract class AuthorizationTestCase extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        self::installRoleStore();
    }

    protected function defineEnvironment($app): void
    {
        parent::defineEnvironment($app);

        $app['config']->set('roster.authorization', true);
    }

    /**
     * Bind Keystone's fakes as the role store, with the default roles. Also
     * what `withRoleStore()` uses in a test outside tests/Authorization.
     */
    public static function installRoleStore(): FakeRoles
    {
        FakePermissions::install();

        return FakeRoles::install()
            ->define('super-admin', [Permissions::SUPER_ADMIN])
            ->define('admin', self::grantedTo('admin'), OrganizationModel::class)
            ->define('member', self::grantedTo('member'), OrganizationModel::class);
    }

    /**
     * The Roster abilities a default role is granted.
     *
     * @return list<string>
     */
    private static function grantedTo(string $role): array
    {
        return array_values(array_filter(
            array_keys(RosterAbilities::ABILITIES),
            fn (string $ability): bool => in_array($role, RosterAbilities::grantTo($ability), true),
        ));
    }
}
