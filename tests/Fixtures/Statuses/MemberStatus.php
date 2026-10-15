<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Tests\Fixtures\Statuses;

use RefactorCircus\Roster\Contracts\AccountStatus;

/**
 * An app's own user statuses: renamed cases for Roster's flows, plus a
 * trial status that lets the user in.
 */
enum MemberStatus: string implements AccountStatus
{
    case Member = 'member';
    case Trial = 'trial';
    case Applied = 'applied';
    case OnHold = 'on_hold';
    case Closed = 'closed';

    public static function default(): static
    {
        return self::Member;
    }

    public static function active(): static
    {
        return self::Member;
    }

    public static function pending(): static
    {
        return self::Applied;
    }

    public static function suspended(): static
    {
        return self::OnHold;
    }

    public static function deactivated(): static
    {
        return self::Closed;
    }

    public function allowsAccess(): bool
    {
        return in_array($this, [self::Member, self::Trial], true);
    }

    public function label(): string
    {
        return ucfirst(str_replace('_', ' ', $this->value));
    }

    public function badge(): string
    {
        return $this->allowsAccess() ? 'success' : 'neutral';
    }
}
