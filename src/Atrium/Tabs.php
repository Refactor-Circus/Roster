<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use InvalidArgumentException;

/**
 * The tabs host applications add to Roster's organization and user screens.
 *
 * Register from a service provider's `boot()`:
 * `app(Tabs::class)->organization(TabDefinition::make('acme.orders')->...)`.
 * The screens read the registry when they render. Registration validates
 * and keeps a copy of the definition, so later calls on the host's instance
 * change nothing. Tabs added to the organization screen follow its built-in
 * tabs; on the user screen they follow an `overview` tab holding the page.
 *
 * All-digit keys become integer array keys; read a tab's key from
 * `$tab->key`, or cast with `(string)`.
 */
final class Tabs
{
    /**
     * The organization screen's built-in tab keys, which registered tabs may
     * not use. New built-in keys may be added in minor releases, so prefix
     * registered keys with a vendor (`vendor.key`).
     *
     * @var list<string>
     */
    public const array ORGANIZATION_TABS = ['members', 'teams', 'invitations', 'roles', 'sso', 'scim', 'mcp', 'activity', 'settings'];

    /**
     * The user screen's default tab, holding the page's own content.
     */
    public const string USER_DEFAULT = 'overview';

    /** @var array<string, TabDefinition> */
    private array $organizations = [];

    /** @var array<string, TabDefinition> */
    private array $users = [];

    /**
     * Add a tab to the organization screen.
     *
     * @throws InvalidArgumentException for a built-in, duplicate or bodiless tab
     */
    public function organization(TabDefinition $tab): self
    {
        $this->organizations = $this->add('organization', $this->organizations, $tab, self::ORGANIZATION_TABS);

        return $this;
    }

    /**
     * Add a tab to the user screen.
     *
     * @throws InvalidArgumentException for `overview`, a duplicate or a bodiless tab
     */
    public function user(TabDefinition $tab): self
    {
        $this->users = $this->add('user', $this->users, $tab, [self::USER_DEFAULT]);

        return $this;
    }

    /**
     * The organization screen's registered tabs, by key, in registration order.
     *
     * @return array<string, TabDefinition>
     */
    public function organizations(): array
    {
        return $this->copies($this->organizations);
    }

    /**
     * The user screen's registered tabs, by key, in registration order.
     *
     * @return array<string, TabDefinition>
     */
    public function users(): array
    {
        return $this->copies($this->users);
    }

    /**
     * Forget every registered tab. For tests only: there is no removing or
     * replacing a tab at runtime.
     */
    public function flush(): void
    {
        $this->organizations = [];
        $this->users = [];
    }

    /**
     * @param  array<string, TabDefinition>  $registered
     * @param  list<string>  $reserved
     * @return array<string, TabDefinition>
     */
    private function add(string $screen, array $registered, TabDefinition $tab, array $reserved): array
    {
        // Validate the copy that is kept, not the caller's instance.
        $tab = clone $tab;
        $key = $tab->key;

        if (in_array($key, $reserved, true)) {
            throw new InvalidArgumentException("Tab key [{$key}] is a built-in {$screen} tab; prefix registered keys with a vendor, such as vendor.key.");
        }

        foreach (array_keys($registered) as $existing) {
            if ((string) $existing === $key) {
                throw new InvalidArgumentException("Tab key [{$key}] is already registered on the {$screen} screen.");
            }
        }

        if ($tab->view === null && $tab->component === null) {
            throw new InvalidArgumentException("Tab [{$key}] on the {$screen} screen needs a view() or a component().");
        }

        $registered[$key] = $tab;

        return $registered;
    }

    /**
     * @param  array<string, TabDefinition>  $tabs
     * @return array<string, TabDefinition>
     */
    private function copies(array $tabs): array
    {
        return array_map(fn (TabDefinition $tab): TabDefinition => clone $tab, $tabs);
    }
}
