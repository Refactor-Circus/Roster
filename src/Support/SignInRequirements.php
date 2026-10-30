<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use Illuminate\Contracts\Container\Container;
use InvalidArgumentException;
use RefactorCircus\Roster\Contracts\SignInRequirement;

/**
 * The sign-in requirements registered through `Roster::requireSignIn()`.
 *
 * Code state filled from providers' `boot()`, so it is a singleton: `Roster`
 * itself is per request, and enforcement must not vanish after the first one
 * under Octane or a queue worker. Class-strings are resolved from the
 * container on first use, once.
 *
 * @internal Register and ask through `Roster`.
 */
final class SignInRequirements
{
    /**
     * In registration order, keyed by class-string or object id.
     *
     * @var array<string, SignInRequirement|string>
     */
    private array $requirements = [];

    public function __construct(private readonly Container $container) {}

    /**
     * Add one; the same class or instance registered again is ignored.
     *
     * @param  SignInRequirement|string  $requirement  an instance, or a class-string checked here
     */
    public function add(SignInRequirement|string $requirement): void
    {
        if (is_string($requirement) && ! is_subclass_of($requirement, SignInRequirement::class)) {
            throw new InvalidArgumentException(sprintf('[%s] is not a %s.', $requirement, SignInRequirement::class));
        }

        $key = is_string($requirement) ? 'class:'.ltrim($requirement, '\\') : 'object:'.spl_object_id($requirement);

        $this->requirements[$key] ??= $requirement;
    }

    public function isEmpty(): bool
    {
        return $this->requirements === [];
    }

    /**
     * Every requirement, in registration order, resolved.
     *
     * @return list<SignInRequirement>
     */
    public function all(): array
    {
        foreach ($this->requirements as $key => $requirement) {
            if (is_string($requirement)) {
                $this->requirements[$key] = $this->resolve($requirement);
            }
        }

        /** @var list<SignInRequirement> */
        return array_values($this->requirements);
    }

    /**
     * @param  string  $class  checked by add()
     */
    private function resolve(string $class): SignInRequirement
    {
        $requirement = $this->container->make($class);

        if (! $requirement instanceof SignInRequirement) {
            throw new InvalidArgumentException(sprintf('[%s] is not a %s.', $class, SignInRequirement::class));
        }

        return $requirement;
    }
}
