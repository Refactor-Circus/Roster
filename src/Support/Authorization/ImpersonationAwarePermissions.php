<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support\Authorization;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Container\Container;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext;

/**
 * Roster's impersonation block around whichever `Permissions` is bound.
 *
 * `RosterServiceProvider` wraps the binding with `$app->extend()`, so checks
 * other packages make through the contract are refused too: while an
 * impersonation is active, every ability matching `roster.impersonation.blocked`
 * (`Str::is` patterns, such as `*.roles.manage`) is denied and lists no scope.
 * Everything else is the wrapped implementation's answer.
 */
final readonly class ImpersonationAwarePermissions implements Permissions
{
    public function __construct(
        private Permissions $permissions,
        private Container $container,
    ) {}

    public function allows(?Authenticatable $user, string $ability, mixed $scope = null): bool
    {
        if ($this->blocked($ability)) {
            return false;
        }

        return $this->permissions->allows($user, $ability, $scope);
    }

    public function scopesWith(?Authenticatable $user, string $ability, string $scopeType): ?array
    {
        if ($this->blocked($ability)) {
            return [];
        }

        return $this->permissions->scopesWith($user, $ability, $scopeType);
    }

    /**
     * The wrapped implementation's list, without the abilities blocked right
     * now; null stays null (unknown).
     */
    public function abilities(?Authenticatable $user, mixed $scope = null): ?array
    {
        $abilities = $this->permissions->abilities($user, $scope);

        if ($abilities === null) {
            return null;
        }

        return array_values(array_filter($abilities, fn (string $ability): bool => ! $this->blocked($ability)));
    }

    public function isSuperAdmin(?Authenticatable $user): bool
    {
        return $this->permissions->isSuperAdmin($user);
    }

    public function forget(?Authenticatable $user = null): void
    {
        $this->permissions->forget($user);
    }

    /**
     * Asked each time: the impersonation context is per request, while the
     * bound implementation may live longer.
     */
    private function blocked(string $ability): bool
    {
        return $this->container->make(ImpersonationContext::class)->isBlocked($ability);
    }
}
