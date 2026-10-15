<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Statuses;

use RefactorCircus\Roster\Contracts\Status;

/**
 * Integer-backed, so not a valid status enum.
 */
enum IntegerStatus: int implements Status
{
    case On = 1;

    public static function default(): static
    {
        return self::On;
    }

    public function label(): string
    {
        return 'On';
    }

    public function badge(): string
    {
        return 'success';
    }
}
