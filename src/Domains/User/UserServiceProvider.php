<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User;

use Illuminate\Auth\Events\Registered;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Routing\Router;
use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Organization\Models\MembershipModel;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\User\Http\Middleware\EnsureUserIsActive;
use RefactorCircus\Roster\Domains\User\Listeners\ApplyRegistrationStatus;
use RefactorCircus\Roster\Domains\User\Models\ProfileModel;
use RefactorCircus\Roster\Support\Tables;

/**
 * Users and their profiles, status and approval.
 */
class UserServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->registerProfileRelation();

        $this->app->make(Router::class)->aliasMiddleware('roster.active', EnsureUserIsActive::class);
        $this->app->make(Dispatcher::class)->listen(Registered::class, ApplyRegistrationStatus::class);

        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
    }

    /**
     * Give a user model without the HasRoster trait the same relations, so
     * Roster works on any model untouched.
     */
    private function registerProfileRelation(): void
    {
        $model = $this->app->make(Repository::class)->get('roster.users.model');

        if (! is_string($model) || ! is_subclass_of($model, Model::class) || method_exists($model, 'rosterProfile')) {
            return;
        }

        $model::resolveRelationUsing(
            'rosterProfile',
            fn (Model $user) => $user->hasOne(ProfileModel::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterMemberships',
            fn (Model $user) => $user->hasMany(MembershipModel::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterRoleAssignments',
            fn (Model $user) => $user->hasMany(RoleAssignmentModel::class, 'user_id'),
        );

        $model::resolveRelationUsing(
            'rosterOrganizations',
            fn (Model $user) => $user->belongsToMany(OrganizationModel::class, Tables::name('memberships'), 'user_id', 'organization_id')->withTimestamps(),
        );
    }
}
