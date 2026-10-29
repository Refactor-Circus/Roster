<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use InvalidArgumentException;
use RefactorCircus\Roster\Domains\Role\Support\BuiltInRoles;

/**
 * Permissions other packages (and Roster's own domains) add to Roster's.
 *
 * `roster:sync-permissions`, `BuiltInRoles::sync()` and the role-tables
 * migration create each as a system permission, and grant it to the named
 * built-in roles only when the sync creates that role: an existing role never
 * gains a newly registered permission, so apps grant it by hand.
 *
 * Packages register from their provider's `boot()`. `roster.*` names are
 * Roster's: its own domains add theirs through `rosterPermission()`.
 *
 * Code-defined state only, so one instance serves the whole process.
 */
final class PermissionCatalogue
{
    private const string RESERVED = 'roster.';

    /**
     * @var array<string, array{description: string, grantTo: array<int, string>}>
     */
    private array $permissions = [];

    /**
     * Add a permission, granted to these built-in roles (`admin`, `member`,
     * `lead`) when the sync creates them.
     *
     * @param  array<int, string>  $grantTo  built-in role slugs
     *
     * @throws InvalidArgumentException for a reserved or taken name, or an unknown role
     */
    public function permission(string $name, string $description, array $grantTo = []): self
    {
        if (str_starts_with($name, self::RESERVED)) {
            throw new InvalidArgumentException("The permission [{$name}] is reserved for Roster: register under your package's own prefix.");
        }

        return $this->add($name, $description, $grantTo);
    }

    /**
     * Add one of Roster's own `roster.*` permissions.
     *
     * @internal Roster's domains only; packages use permission().
     *
     * @param  array<int, string>  $grantTo  built-in role slugs
     *
     * @throws InvalidArgumentException for a name outside `roster.*`, a taken one, or an unknown role
     */
    public function rosterPermission(string $name, string $description, array $grantTo = []): self
    {
        if (! str_starts_with($name, self::RESERVED)) {
            throw new InvalidArgumentException("The permission [{$name}] is not Roster's: register it with permission().");
        }

        return $this->add($name, $description, $grantTo);
    }

    public function has(string $name): bool
    {
        return isset($this->permissions[$name]);
    }

    /**
     * Every registered permission, in registration order.
     *
     * @return array<string, array{description: string, grantTo: array<int, string>}>
     */
    public function all(): array
    {
        return $this->permissions;
    }

    /**
     * The registered permissions granted to a built-in role.
     *
     * @return array<int, string>
     */
    public function grantedTo(string $role): array
    {
        return array_keys(array_filter(
            $this->permissions,
            fn (array $permission): bool => in_array($role, $permission['grantTo'], true),
        ));
    }

    /**
     * @param  array<int, string>  $grantTo
     */
    private function add(string $name, string $description, array $grantTo): self
    {
        if (isset($this->permissions[$name]) || array_key_exists($name, BuiltInRoles::PERMISSIONS)) {
            throw new InvalidArgumentException("The permission [{$name}] is already registered.");
        }

        $roles = array_keys(BuiltInRoles::roles());

        foreach ($grantTo as $role) {
            if (! in_array($role, $roles, true)) {
                throw new InvalidArgumentException("The permission [{$name}] is granted to [{$role}], which is not a built-in role (".implode(', ', $roles).').');
            }
        }

        $this->permissions[$name] = ['description' => $description, 'grantTo' => array_values($grantTo)];

        return $this;
    }
}
