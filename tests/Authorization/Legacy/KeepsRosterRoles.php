<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Authorization\Legacy;

/**
 * Keeps Roster's own role tables behind Keystone's contracts (the interim
 * adapter) instead of installing Keystone's fakes, for the tests of Roster's
 * role and permission API itself.
 *
 * @internal Deleted with `tests/Authorization/Legacy/` and the Role and
 *           Permission domains.
 */
trait KeepsRosterRoles
{
    protected function installRoleStore(): void {}
}
