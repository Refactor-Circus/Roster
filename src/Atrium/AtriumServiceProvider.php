<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Atrium;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage as InvitationPageContract;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Permission\Services\Authorizer;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;

/**
 * Roster's Atrium seam, registered by RosterServiceProvider only while
 * Atrium is installed. It points the invitation page and the impersonation
 * banner at their Atrium views and registers `@rosterCan` for the screens.
 * Without it, Roster renders their plain views.
 */
final class AtriumServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(InvitationPageContract::class, InvitationPage::class);
    }

    public function boot(): void
    {
        // @rosterCan('roster.users.update', $scope, $self) ... @endrosterCan:
        // the signed-in user's Roster permission check, so a control shows
        // only when its action is allowed.
        Blade::if('rosterCan', function (string $permission, OrganizationModel|TeamModel|null $scope = null, ?Model $self = null): bool {
            $user = request()->user();

            return app(Authorizer::class)->check($user instanceof Model ? $user : null, $permission, $scope, $self);
        });

        // roster::impersonation-banner picks Atrium's banner by this.
        View::share('rosterUsesAtrium', true);
    }
}
