<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Enums;

use RefactorCircus\Roster\Contracts\Status;

/**
 * Roster's default organization statuses (`roster.statuses.organizations`).
 * Stored and shown only: Roster grants and refuses nothing by them.
 */
enum OrganizationStatus: string implements Status
{
    case Active = 'active';
    case Inactive = 'inactive';

    public static function default(): static
    {
        return self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => __('roster::roster.status_active'),
            self::Inactive => __('roster::roster.status_inactive'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Inactive => 'neutral',
        };
    }
}
