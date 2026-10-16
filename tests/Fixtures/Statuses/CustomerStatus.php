<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Statuses;

use RefactorCircus\Roster\Contracts\Status;

/**
 * An app's own organization statuses, with a non-default starting status.
 */
enum CustomerStatus: string implements Status
{
    case Prospect = 'prospect';
    case Customer = 'customer';
    case Blocked = 'blocked';

    public static function default(): static
    {
        return self::Prospect;
    }

    public function label(): string
    {
        return ucfirst($this->value);
    }

    public function badge(): string
    {
        return $this === self::Blocked ? 'danger' : 'success';
    }
}
