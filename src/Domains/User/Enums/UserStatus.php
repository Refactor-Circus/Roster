<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Enums;

use RefactorCircus\Roster\Contracts\AccountStatus;

/**
 * Roster's default user statuses (`roster.statuses.users`).
 */
enum UserStatus: string implements AccountStatus
{
    case Active = 'active';
    // Waiting for an approver to accept the account.
    case Pending = 'pending';
    case Suspended = 'suspended';
    case Deactivated = 'deactivated';

    public static function default(): static
    {
        return self::Active;
    }

    public static function active(): static
    {
        return self::Active;
    }

    public static function pending(): static
    {
        return self::Pending;
    }

    public static function suspended(): static
    {
        return self::Suspended;
    }

    public static function deactivated(): static
    {
        return self::Deactivated;
    }

    public function allowsAccess(): bool
    {
        return $this === self::Active;
    }

    public function label(): string
    {
        return match ($this) {
            self::Active => __('roster::roster.status_active'),
            self::Pending => __('roster::roster.status_pending'),
            self::Suspended => __('roster::roster.status_suspended'),
            self::Deactivated => __('roster::roster.status_deactivated'),
        };
    }

    public function badge(): string
    {
        return match ($this) {
            self::Active => 'success',
            self::Pending => 'info',
            self::Suspended => 'warning',
            self::Deactivated => 'neutral',
        };
    }
}
