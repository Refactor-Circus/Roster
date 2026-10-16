<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Support;

use InvalidArgumentException;
use RefactorCircus\Roster\Contracts\AccountStatus;
use RefactorCircus\Roster\Contracts\Status;
use RefactorCircus\Roster\Domains\Organization\Enums\OrganizationStatus;
use RefactorCircus\Roster\Domains\Team\Enums\TeamStatus;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use ReflectionEnum;

/**
 * The status enum each of Roster's records uses, from the `roster.statuses`
 * config key. Models cast to it, validation lists its cases and Roster's
 * flows ask it for the cases they move users between, so an app swaps the
 * statuses by pointing the key at its own enum.
 */
final class Statuses
{
    /**
     * @var array<string, class-string<Status>>
     */
    public const array DEFAULTS = [
        'users' => UserStatus::class,
        'organizations' => OrganizationStatus::class,
        'teams' => TeamStatus::class,
    ];

    /**
     * @return class-string<AccountStatus>
     */
    public static function users(): string
    {
        /** @var class-string<AccountStatus> */
        return self::resolve('users', AccountStatus::class);
    }

    /**
     * @return class-string<Status>
     */
    public static function organizations(): string
    {
        return self::resolve('organizations', Status::class);
    }

    /**
     * @return class-string<Status>
     */
    public static function teams(): string
    {
        return self::resolve('teams', Status::class);
    }

    /**
     * The user status a stored or given value names.
     */
    public static function user(AccountStatus|string $status): AccountStatus
    {
        return $status instanceof AccountStatus ? $status : self::users()::from($status);
    }

    /**
     * The organization status a stored or given value names.
     */
    public static function organization(Status|string $status): Status
    {
        return $status instanceof Status ? $status : self::organizations()::from($status);
    }

    /**
     * The team status a stored or given value names.
     */
    public static function team(Status|string $status): Status
    {
        return $status instanceof Status ? $status : self::teams()::from($status);
    }

    /**
     * Every value of a record's statuses, for validation and schemas.
     *
     * @param  class-string<Status>  $enum
     * @return list<string>
     */
    public static function values(string $enum): array
    {
        return array_map(fn (Status $status): string => (string) $status->value, $enum::cases());
    }

    /**
     * @template T of Status
     *
     * @param  class-string<T>  $contract
     * @return class-string<T>
     */
    private static function resolve(string $key, string $contract): string
    {
        $enum = config('roster.statuses.'.$key, self::DEFAULTS[$key]);

        if (! is_string($enum) || ! enum_exists($enum) || ! is_subclass_of($enum, $contract) || (string) (new ReflectionEnum($enum))->getBackingType() !== 'string') {
            throw new InvalidArgumentException(sprintf('roster.statuses.%s must name a string-backed enum implementing %s.', $key, $contract));
        }

        /** @var class-string<T> */
        return $enum;
    }
}
