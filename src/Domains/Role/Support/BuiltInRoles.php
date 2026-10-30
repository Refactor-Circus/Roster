<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Support;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

/**
 * Roster's core permissions and default roles. Permissions its domains and
 * other packages register in the PermissionCatalogue are synced with them.
 *
 * Seeded by the roles migration and re-synced by `roster:sync-permissions`.
 * Syncing adds anything missing and never removes or rewrites what an admin
 * has changed, so it is safe to run on every deploy.
 */
final class BuiltInRoles
{
    /**
     * @var array<string, string>
     */
    public const array PERMISSIONS = [
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
        'roster.roles.view' => 'View roles and permissions.',
        'roster.roles.manage' => 'Create, edit and delete roles and permissions.',
        'roster.roles.assign' => 'Assign and revoke roles.',
        'roster.audit.view' => "Read Roster's history in the audit log.",
        'atrium.view' => 'Open the Atrium dashboard.',
    ];

    /**
     * Default roles: slug => [name, scope, super, permissions].
     *
     * @return array<string, array{name: string, scope: RoleScope, super: bool, permissions: array<int, string>}>
     */
    public static function roles(): array
    {
        $organizationWide = array_values(array_filter(
            array_keys(self::PERMISSIONS),
            fn (string $permission): bool => str_starts_with($permission, 'roster.')
                && ! str_starts_with($permission, 'roster.users.')
                && ! in_array($permission, ['roster.organizations.create', 'roster.organizations.sync', 'roster.organizations.purge'], true),
        ));

        return [
            'super-admin' => ['name' => 'Super admin', 'scope' => RoleScope::Global, 'super' => true, 'permissions' => []],
            'admin' => ['name' => 'Admin', 'scope' => RoleScope::Organization, 'super' => false, 'permissions' => $organizationWide],
            'member' => ['name' => 'Member', 'scope' => RoleScope::Organization, 'super' => false, 'permissions' => [
                'roster.organizations.view',
                'roster.members.view',
                'roster.teams.view',
            ]],
            'lead' => ['name' => 'Team lead', 'scope' => RoleScope::Team, 'super' => false, 'permissions' => [
                'roster.teams.view',
                'roster.teams.manage',
            ]],
        ];
    }

    /**
     * Create every missing permission - Roster's own and those registered in
     * the PermissionCatalogue - and every missing default role. A role the
     * sync creates gets its permissions and the registered ones granted to
     * it; existing roles and permissions are left exactly as they are.
     *
     * @return array<int, string> system permissions in the database that
     *                            nothing registers any more, left in place
     */
    public static function sync(): array
    {
        $catalogue = app(PermissionCatalogue::class);

        return DB::transaction(function () use ($catalogue): array {
            $permissions = self::PERMISSIONS + array_map(
                fn (array $permission): string => $permission['description'],
                $catalogue->all(),
            );

            foreach ($permissions as $name => $description) {
                PermissionModel::query()->firstOrCreate(['name' => $name], ['description' => $description, 'system' => true]);
            }

            foreach (self::roles() as $slug => $definition) {
                $role = RoleModel::query()
                    ->whereNull('organization_id')
                    ->where('scope', $definition['scope'])
                    ->where('slug', $slug)
                    ->first();

                if ($role !== null) {
                    continue;
                }

                $role = RoleModel::query()->create([
                    'name' => $definition['name'],
                    'slug' => $slug,
                    'scope' => $definition['scope'],
                    'super' => $definition['super'],
                    'system' => true,
                ]);

                $granted = [...$definition['permissions'], ...$catalogue->grantedTo($slug)];

                $role->permissions()->sync(PermissionModel::query()->whereIn('name', $granted)->pluck('id'));
            }

            return PermissionModel::query()
                ->where('system', true)
                ->whereNotIn('name', array_keys($permissions))
                ->orderBy('name')
                ->pluck('name')
                ->map(strval(...))
                ->all();
        });
    }
}
