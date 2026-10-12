<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Closure;
use Illuminate\Contracts\Cache\Factory;
use Illuminate\Contracts\Cache\Repository as Cache;
use Illuminate\Contracts\Config\Repository;

/**
 * The permission catalogue and each user's role grants, kept in the cache
 * store between requests when `roster.permission_cache.ttl` is above 0.
 *
 * Entries live under version keys: replacing a version drops every entry
 * filed under the old one at once, on every server sharing the store.
 * Permissions keeps its own per-request copy on top of this.
 */
final readonly class PermissionCache
{
    private const string CATALOGUE_VERSION = 'roster:permissions:catalogue-version';

    private const string GRANTS_VERSION = 'roster:permissions:grants-version';

    public function __construct(
        private Repository $config,
        private Factory $cache,
    ) {}

    public function enabled(): bool
    {
        return $this->ttl() > 0;
    }

    /**
     * Every permission name, from the cache when enabled.
     *
     * @param  Closure(): array<int, string>  $load
     * @return array<int, string>
     */
    public function names(Closure $load): array
    {
        if (! $this->enabled()) {
            return $load();
        }

        $store = $this->store();

        return $store->remember('roster:permissions:'.$this->version($store, self::CATALOGUE_VERSION).':names', $this->ttl(), $load);
    }

    /**
     * A user's grants, from the cache when enabled.
     *
     * @template TGrants of array
     *
     * @param  Closure(): TGrants  $load
     * @return TGrants
     */
    public function grants(string $user, Closure $load): array
    {
        if (! $this->enabled()) {
            return $load();
        }

        $store = $this->store();

        return $store->remember($this->grantsKey($store, $user), $this->ttl(), $load);
    }

    /**
     * Drop one user's cached grants.
     */
    public function forgetUser(string $user): void
    {
        if ($this->enabled()) {
            $store = $this->store();
            $store->forget($this->grantsKey($store, $user));
        }
    }

    /**
     * Drop every user's cached grants and, with `$catalogue`, the permission
     * names too.
     */
    public function forget(bool $catalogue = false): void
    {
        if (! $this->enabled()) {
            return;
        }

        $store = $this->store();
        $store->forever(self::GRANTS_VERSION, bin2hex(random_bytes(8)));

        if ($catalogue) {
            $store->forever(self::CATALOGUE_VERSION, bin2hex(random_bytes(8)));
        }
    }

    private function grantsKey(Cache $store, string $user): string
    {
        return 'roster:permissions:'.$this->version($store, self::CATALOGUE_VERSION).':'.$this->version($store, self::GRANTS_VERSION).':grants:'.$user;
    }

    private function version(Cache $store, string $key): string
    {
        $version = $store->get($key);

        return is_string($version) ? $version : '0';
    }

    private function ttl(): int
    {
        $ttl = $this->config->get('roster.permission_cache.ttl', 0);

        return is_numeric($ttl) ? (int) $ttl : 0;
    }

    private function store(): Cache
    {
        $store = $this->config->get('roster.permission_cache.store');

        return $this->cache->store(is_string($store) && $store !== '' ? $store : null);
    }
}
