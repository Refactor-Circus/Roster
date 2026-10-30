<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Services;

use Illuminate\Database\Eloquent\Model;
use InvalidArgumentException;

/**
 * The models whose policies answer each permission prefix.
 *
 * A permission `{prefix}.{verb}` is answered by the Gate policy of the model
 * registered for its prefix (everything before the last dot), as the
 * camel-cased verb: with `resource('acme.widgets', WidgetModel::class)`,
 * `acme.widgets.approve` asks `WidgetPolicy::approve()`.
 *
 * Packages register from their provider's `boot()`. `roster.*` is Roster's:
 * its core resources are registered by PermissionServiceProvider before any
 * provider boots, and its own domains add theirs through `rosterResource()`.
 *
 * Code-defined state only, so one instance serves the whole process.
 */
final class Resources
{
    private const string PREFIX = '/^[a-z0-9-]+\.[a-z0-9-]+$/D';

    private const string RESERVED = 'roster.';

    /**
     * @var array<string, class-string<Model>>
     */
    private array $resources = [];

    private int $generation = 0;

    /**
     * Let `$model`'s policy answer permissions named `{$prefix}.{verb}`.
     *
     * @param  class-string<Model>  $model
     *
     * @throws InvalidArgumentException for an invalid, reserved or taken prefix
     */
    public function resource(string $prefix, string $model): self
    {
        if ($this->reserved($prefix)) {
            throw new InvalidArgumentException("The resource prefix [{$prefix}] is reserved for Roster: register under your package's own prefix.");
        }

        return $this->add($prefix, $model);
    }

    /**
     * Register one of Roster's own `roster.*` resources.
     *
     * @internal Roster's domains only; packages use resource().
     *
     * @param  class-string<Model>  $model
     *
     * @throws InvalidArgumentException for a prefix outside `roster.*`, or a taken one
     */
    public function rosterResource(string $prefix, string $model): self
    {
        if (! $this->reserved($prefix)) {
            throw new InvalidArgumentException("The resource prefix [{$prefix}] is not Roster's: register it with resource().");
        }

        return $this->add($prefix, $model);
    }

    /**
     * Whether a prefix belongs to Roster (`roster.*`).
     */
    public function reserved(string $prefix): bool
    {
        return str_starts_with($prefix, self::RESERVED);
    }

    /**
     * The model registered for a prefix, or null.
     *
     * @return class-string<Model>|null
     */
    public function model(string $prefix): ?string
    {
        return $this->resources[$prefix] ?? null;
    }

    /**
     * Every registered prefix and its model, in registration order.
     *
     * @return array<string, class-string<Model>>
     */
    public function all(): array
    {
        return $this->resources;
    }

    /**
     * Bumped on every registration, so memos built on these (the
     * Authorizer's) know to start over.
     */
    public function generation(): int
    {
        return $this->generation;
    }

    /**
     * Callers may pass any string at runtime, whatever the signature says.
     */
    private function add(string $prefix, string $model): self
    {
        if (preg_match(self::PREFIX, $prefix) !== 1) {
            throw new InvalidArgumentException("The resource prefix [{$prefix}] must be two lower-case segments of letters, digits and dashes, such as [acme.widgets].");
        }

        if (isset($this->resources[$prefix])) {
            throw new InvalidArgumentException("The resource prefix [{$prefix}] is already registered, for [{$this->resources[$prefix]}].");
        }

        if (! is_subclass_of($model, Model::class)) {
            throw new InvalidArgumentException("The resource prefix [{$prefix}] needs an Eloquent model class; [{$model}] is not one.");
        }

        $this->resources[$prefix] = $model;
        $this->generation++;

        return $this;
    }
}
