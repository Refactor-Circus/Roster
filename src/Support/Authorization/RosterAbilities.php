<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support\Authorization;

use Illuminate\Support\Str;
use RefactorCircus\Keystone\Authorization\Abilities;

/**
 * The abilities Roster checks, declared in Keystone's `Abilities` registry
 * from `RosterServiceProvider::boot()`, so a role store can list and grant
 * them.
 *
 * Role management is the role store's own, so no `roster.roles.*` ability is
 * declared. `grantTo` names the default roles a role store may seed with each
 * ability: `admin` (organization-wide) and `member`; a team role is the role
 * store's concern.
 */
final class RosterAbilities
{
    /**
     * Every ability Roster declares, with its description.
     *
     * @var array<string, string>
     */
    public const array ABILITIES = [
        'roster.users.view' => 'View users.',
        'roster.users.create' => 'Create users.',
        'roster.users.update' => 'Edit users, their profiles and context.',
        'roster.users.delete' => 'Delete and restore users.',
        'roster.users.purge' => 'Permanently delete deleted users.',
        'roster.users.manage-status' => 'Suspend, deactivate and reactivate users.',
        'roster.users.approve' => 'Approve or reject accounts awaiting approval.',
        'roster.users.impersonate' => 'Act as another user, temporarily and on the record.',
        'roster.organizations.view' => 'View organizations.',
        'roster.organizations.create' => 'Create organizations.',
        'roster.organizations.update' => "Edit an organization's settings.",
        'roster.organizations.delete' => 'Delete and restore organizations.',
        'roster.organizations.purge' => 'Permanently delete deleted organizations.',
        'roster.organizations.transfer' => 'Transfer organization ownership.',
        'roster.organizations.sync' => 'Create and update organizations from external systems.',
        'roster.members.view' => 'View organization members.',
        'roster.members.manage' => 'Add and remove organization members.',
        'roster.teams.view' => 'View teams and their members.',
        'roster.teams.manage' => 'Create, edit and delete teams and seat members.',
        'roster.invitations.view' => 'View invitations.',
        'roster.invitations.manage' => 'Send and revoke invitations.',
        'roster.audit.view' => "Read Roster's history in the audit log.",
    ];

    /**
     * Abilities the organization-wide `admin` role does not get: user
     * administration and the organization lifecycle outside one organization.
     *
     * @var array<int, string>
     */
    private const array NOT_FOR_ADMIN = ['roster.users.*', 'roster.organizations.create', 'roster.organizations.sync', 'roster.organizations.purge'];

    /**
     * What the default `member` role holds.
     *
     * @var array<int, string>
     */
    private const array FOR_MEMBER = ['roster.organizations.view', 'roster.members.view', 'roster.teams.view'];

    /**
     * Define every Roster ability in the registry.
     */
    public static function define(Abilities $abilities): void
    {
        foreach (self::ABILITIES as $ability => $description) {
            $abilities->define($ability, $description, self::grantTo($ability));
        }
    }

    /**
     * The default roles an ability is granted to.
     *
     * @return array<int, string>
     */
    public static function grantTo(string $ability): array
    {
        $roles = [];

        if (! Str::is(self::NOT_FOR_ADMIN, $ability)) {
            $roles[] = 'admin';
        }

        if (in_array($ability, self::FOR_MEMBER, true)) {
            $roles[] = 'member';
        }

        return $roles;
    }
}
