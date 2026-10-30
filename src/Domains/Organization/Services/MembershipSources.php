<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Services;

use InvalidArgumentException;
use RefactorCircus\Roster\Domains\Organization\Enums\MembershipSource;

/**
 * The ways a user can join an organization: Roster's built-in sources (the
 * `MembershipSource` cases) plus the ones other packages register, with the
 * label members show for each.
 *
 * Packages register from their provider's `boot()`. A stored source that
 * nothing registers still shows, as its stored value.
 *
 * Code-defined state only, so one instance serves the whole process.
 */
final class MembershipSources
{
    /**
     * The `memberships.source` column's length.
     */
    private const int MAX_LENGTH = 32;

    /**
     * @var array<string, string> label by registered source
     */
    private array $registered = [];

    /**
     * Add a source and the label its members show.
     *
     * @throws InvalidArgumentException for a built-in or taken source, or one the column cannot hold
     */
    public function register(string $source, string $label): self
    {
        if ($source === '' || strlen($source) > self::MAX_LENGTH) {
            throw new InvalidArgumentException('The membership source ['.$source.'] must be 1 to '.self::MAX_LENGTH.' characters.');
        }

        if (MembershipSource::tryFrom($source) !== null) {
            throw new InvalidArgumentException("The membership source [{$source}] is built in.");
        }

        if (isset($this->registered[$source])) {
            throw new InvalidArgumentException("The membership source [{$source}] is already registered.");
        }

        $this->registered[$source] = $label;

        return $this;
    }

    /**
     * Whether the source is built in or registered.
     */
    public function has(string $source): bool
    {
        return MembershipSource::tryFrom($source) !== null || isset($this->registered[$source]);
    }

    /**
     * What members of the source show: the registered label, or the stored
     * value itself for a built-in source or one nothing registers.
     */
    public function label(string $source): string
    {
        return $this->registered[$source] ?? $source;
    }

    /**
     * Every source with its label, built-in ones first.
     *
     * @return array<string, string>
     */
    public function all(): array
    {
        $builtIn = array_column(MembershipSource::cases(), 'value');

        return array_combine($builtIn, $builtIn) + $this->registered;
    }
}
