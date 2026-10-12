<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use Illuminate\Support\Facades\DB;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionDeletedActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionDeletingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

final class DeleteSsoConnectionAction
{
    /**
     * Remove a connection and its linked identities. Accounts stay.
     */
    public function execute(SsoConnectionModel $connection): void
    {
        SsoConnectionDeletingActionEvent::dispatch($connection);

        DB::transaction(fn () => $connection->delete());

        SsoConnectionDeletedActionEvent::dispatch($connection);
    }
}
