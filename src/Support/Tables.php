<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use RefactorCircus\Keystone\Database\Tables as KeystoneTables;

/**
 * The table each of Roster's records lives in, from the `roster.tables`
 * config key. Models, migrations, validation rules and joins all ask here,
 * so renaming a table is one config change made before the migrations run.
 *
 * A published config that lists only some tables keeps the defaults for the
 * rest.
 */
final class Tables extends KeystoneTables
{
    protected const string CONFIG = 'roster.tables';

    /**
     * @var array<string, string>
     */
    public const array DEFAULTS = [
        'users' => 'users',
        'profiles' => 'roster_profiles',
        'organizations' => 'roster_organizations',
        'organization_domains' => 'roster_organization_domains',
        'organization_links' => 'roster_organization_links',
        'memberships' => 'roster_memberships',
        'teams' => 'roster_teams',
        'team_members' => 'roster_team_members',
        'invitations' => 'roster_invitations',
        'permissions' => 'roster_permissions',
        'roles' => 'roster_roles',
        'permission_role' => 'roster_permission_role',
        'role_assignments' => 'roster_role_assignments',
        'impersonations' => 'roster_impersonations',
        'transfers' => 'roster_transfers',
        'transfer_rows' => 'roster_transfer_rows',
    ];
}
