<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Support\Users;

/**
 * Whether the signed-in user may use a permission, asked the way the Atrium
 * screens ask it. Controllers refuse with it and views hide controls with it
 * (as `@rosterCan`), so a control is shown exactly when its action is allowed.
 */
final class ScreenAccess
{
    public static function allows(string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): bool
    {
        $user = request()->user();

        return app(Authorizer::class)->check($user instanceof Model ? $user : null, $permission, $scope, $self);
    }

    /**
     * Whether the signed-in user may use a permission on a screen's record
     * (or a list row), in the scope the screens use for that record: its
     * organization or team, the user it is about, or globally without one.
     * A record of any other type is refused, so a new record type must be
     * added here before its screen can grant anything.
     */
    public static function allowsOn(string $permission, ?Model $record): bool
    {
        $users = app(Users::class)->model();

        return match (true) {
            $record === null => self::allows($permission),
            $record instanceof OrganizationModel, $record instanceof TeamModel => self::allows($permission, $record),
            $record instanceof $users => self::allows($permission, null, $record),
            $record instanceof RoleModel, $record instanceof ImpersonationModel, $record instanceof InvitationModel => self::allows($permission, $record->organization),
            $record instanceof TransferModel => self::allows($permission, $record->organization, $record->requester),
            default => false,
        };
    }

    /**
     * The organizations the signed-in user holds a permission in; null when
     * they hold it globally.
     *
     * @return array<int, int|string>|null
     */
    public static function organizations(string $permission): ?array
    {
        $user = request()->user();

        return app(Authorizer::class)->organizationsWith($user instanceof Model ? $user : null, $permission);
    }
}
