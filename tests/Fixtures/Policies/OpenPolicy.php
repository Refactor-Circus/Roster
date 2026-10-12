<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Policies;

use Illuminate\Database\Eloquent\Model;

/**
 * An application's policy that allows every ability to every signed-in user.
 */
final class OpenPolicy
{
    /**
     * @param  array<int, mixed>  $arguments
     */
    public function __call(string $ability, array $arguments): bool
    {
        return ($arguments[0] ?? null) instanceof Model;
    }
}
