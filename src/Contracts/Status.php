<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Contracts;

use BackedEnum;

/**
 * A status Roster stores on a record: a string-backed enum named in
 * `roster.statuses`. Organizations and teams take any enum implementing
 * this; Roster only stores and shows the value.
 */
interface Status extends BackedEnum
{
    /**
     * The status new records start with.
     */
    public static function default(): static;

    /**
     * The status as shown to people.
     */
    public function label(): string;

    /**
     * The Atrium badge variant the status shows with: `success`, `info`,
     * `warning`, `danger`, `primary` or `neutral`.
     */
    public function badge(): string;
}
