<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation;

use RefactorCircus\Keystone\Support\ServiceProvider;
use RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage;
use RefactorCircus\Roster\Domains\Invitation\Services\PlainInvitationPage;

/**
 * Email invitations to an organization, and the page they link to.
 */
class InvitationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // The plain page; Roster's Atrium seam replaces it while Atrium is installed.
        $this->app->singleton(InvitationPage::class, PlainInvitationPage::class);
    }

    public function boot(): void
    {
        $this->loadApiRoutesFrom(__DIR__.'/routes.php');
        $this->loadRoutesFrom(__DIR__.'/web.php');
    }
}
