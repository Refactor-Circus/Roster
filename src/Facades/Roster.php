<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static bool auditing() Whether Roster records to the suite's audit log (refactor-circus/keen installed, `roster.audit.enabled` on).
 * @method static \RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel|null organization(\Illuminate\Database\Eloquent\Model $user)
 * @method static \RefactorCircus\Roster\Domains\Team\Models\TeamModel|null team(\Illuminate\Database\Eloquent\Model $user)
 * @method static void requireSignIn(\RefactorCircus\Roster\Contracts\SignInRequirement|class-string<\RefactorCircus\Roster\Contracts\SignInRequirement> $requirement) Register a sign-in requirement, from a provider's boot().
 * @method static \RefactorCircus\Roster\Data\ExternalSignIn|null externalSignInFor(string $email) The sign-in this email must use instead of a password; null for super-admins.
 * @method static \RefactorCircus\Roster\Contracts\AccountStatus provisionedStatus(\RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel $organization) The status the organization's externally provisioned accounts start with; active when unset or invalid (reported).
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
