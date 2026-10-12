<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool auditing() Whether Roster records to the suite's audit log (refactor-circus/keen installed, `roster.audit.enabled` on).
 * @method static \RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel|null organization(\Illuminate\Database\Eloquent\Model $user)
 * @method static \RefactorCircus\Roster\Domains\Team\Models\TeamModel|null team(\Illuminate\Database\Eloquent\Model $user)
 *
 * @see \RefactorCircus\Roster\Roster
 */
class Roster extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \RefactorCircus\Roster\Roster::class;
    }
}
