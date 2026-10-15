<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Contracts;

/**
 * A user's account status: a string-backed enum named in
 * `roster.statuses.users`. Roster's approval, suspension and deactivation
 * flows move users between the cases named here; an enum may add more,
 * deciding through `allowsAccess()` whether they let the user in.
 */
interface AccountStatus extends Status
{
    /**
     * The status of a user allowed in, and of a user with no profile row.
     */
    public static function active(): static;

    /**
     * The status of a user waiting for an approver.
     */
    public static function pending(): static;

    /**
     * The status SuspendUserAction sets: a temporary block.
     */
    public static function suspended(): static;

    /**
     * The status DeactivateUserAction and RejectUserAction set.
     */
    public static function deactivated(): static;

    /**
     * Whether a user with this status may sign in and use the app.
     */
    public function allowsAccess(): bool;
}
