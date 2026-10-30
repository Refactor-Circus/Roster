<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains;

use Illuminate\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Impersonation\ImpersonationServiceProvider;
use RefactorCircus\Roster\Domains\Invitation\InvitationServiceProvider;
use RefactorCircus\Roster\Domains\Organization\OrganizationServiceProvider;
use RefactorCircus\Roster\Domains\Permission\PermissionServiceProvider;
use RefactorCircus\Roster\Domains\Role\RoleServiceProvider;
use RefactorCircus\Roster\Domains\Team\TeamServiceProvider;
use RefactorCircus\Roster\Domains\Transfer\TransferServiceProvider;
use RefactorCircus\Roster\Domains\User\UserServiceProvider;

class DomainServiceProvider extends ServiceProvider
{
    /**
     * The domain service providers.
     *
     * @var array<int, class-string<ServiceProvider>>
     */
    private array $providers = [
        UserServiceProvider::class,
        OrganizationServiceProvider::class,
        TeamServiceProvider::class,
        InvitationServiceProvider::class,
        RoleServiceProvider::class,
        PermissionServiceProvider::class,
        ImpersonationServiceProvider::class,
        TransferServiceProvider::class,
    ];

    public function register(): void
    {
        foreach ($this->providers as $provider) {
            $this->app->register($provider);
        }
    }
}
