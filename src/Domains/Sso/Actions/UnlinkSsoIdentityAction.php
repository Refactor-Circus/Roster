<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentityUnlinkedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoIdentityUnlinkingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;

final class UnlinkSsoIdentityAction
{
    /**
     * Unlink an identity; signing in through that provider would then go
     * through the linking rules again.
     */
    public function execute(SsoIdentityModel $identity): void
    {
        SsoIdentityUnlinkingActionEvent::dispatch($identity);

        DB::transaction(fn () => $identity->delete());

        SsoIdentityUnlinkedActionEvent::dispatch($identity);
    }
}
