<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use InvalidArgumentException;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use Throwable;

/**
 * A tab a host application adds to Roster's organization or user screen,
 * registered on `Tabs` from a provider's `boot()`.
 *
 * Every property is publicly readable but only writable from inside the
 * class, so a definition is configured through its fluent setters. The
 * resolver and authorize closures are private: Roster calls them through
 * resolveData() and allows().
 *
 * A tab is hidden unless it says who may see it: a Roster permission(), an
 * authorize() closure (both must pass when it has both), or public().
 */
final class TabDefinition
{
    /**
     * Keys go into `?tab=`, `data-testid` and URLs.
     */
    private const string KEY_PATTERN = '/^[a-z0-9][a-z0-9._-]*$/';

    public private(set) string $label;

    /**
     * An Atrium icon name.
     */
    public private(set) ?string $icon = 'squares-2x2';

    public private(set) ?string $view = null;

    public private(set) ?string $component = null;

    public private(set) ?string $permission = null;

    public private(set) bool $public = false;

    /** @var (Closure(Model): array<string, mixed>)|null */
    private ?Closure $resolver = null;

    /** @var (Closure(Request, Model): bool)|null */
    private ?Closure $authorize = null;

    final public function __construct(public readonly string $key)
    {
        if (preg_match(self::KEY_PATTERN, $key) !== 1) {
            throw new InvalidArgumentException("Tab key [{$key}] must match ".self::KEY_PATTERN.'; use lowercase letters, digits, dots, dashes and underscores, such as vendor.key.');
        }

        $this->label = $key;
    }

    public static function make(string $key): self
    {
        return new self($key);
    }

    public function label(string $label): self
    {
        $this->label = $label;

        return $this;
    }

    public function icon(string $icon): self
    {
        $this->icon = $icon;

        return $this;
    }

    /**
     * Render the tab with a Blade view.
     */
    public function view(string $view): self
    {
        $this->view = $view;
        $this->component = null;

        return $this;
    }

    /**
     * Render the tab with a Blade component.
     */
    public function component(string $component): self
    {
        $this->component = $component;
        $this->view = null;

        return $this;
    }

    /**
     * The data the view or component gets, from the screen's record. Runs
     * only for the open tab, after the viewer's access is checked.
     *
     * @param  Closure(Model): array<string, mixed>  $resolver
     */
    public function resolve(Closure $resolver): self
    {
        $this->resolver = $resolver;

        return $this;
    }

    /**
     * Show the tab only to viewers holding this Roster permission, checked
     * in the organization screen's organization, or for the user screen's
     * user.
     */
    public function permission(string $permission): self
    {
        $this->permission = $permission;

        return $this;
    }

    /**
     * Show the tab only when the callback answers true. It runs on every
     * render of the screen, whatever the request's method; one that throws
     * hides the tab and is reported.
     *
     * @param  Closure(Request, Model): bool  $callback
     */
    public function authorize(Closure $callback): self
    {
        $this->authorize = $callback;

        return $this;
    }

    /**
     * Show the tab to anyone who may open the screen.
     */
    public function public(): self
    {
        $this->public = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveData(Model $record): array
    {
        return $this->resolver === null ? [] : ($this->resolver)($record);
    }

    /**
     * Whether the viewer may see the tab on this record: the permission
     * through ScreenAccess, then the closure. A closure that throws counts
     * as a refusal and is reported once per request.
     */
    public function allows(Request $request, Model $record, ?OrganizationModel $scope, ?Model $self): bool
    {
        if ($this->permission === null && $this->authorize === null) {
            return $this->public;
        }

        if ($this->permission !== null && ! ScreenAccess::allows($this->permission, $scope, $self)) {
            return false;
        }

        if ($this->authorize === null) {
            return true;
        }

        try {
            return ($this->authorize)($request, $record) === true;
        } catch (Throwable $exception) {
            app(TabVisibility::class)->report($this->key, $exception);

            return false;
        }
    }
}
