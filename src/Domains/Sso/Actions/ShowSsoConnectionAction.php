<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Actions;

use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionShowingActionEvent;
use RefactorCircus\Roster\Domains\Sso\Events\SsoConnectionShownActionEvent;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

final class ShowSsoConnectionAction
{
    public function execute(SsoConnectionModel $connection): SsoConnectionModel
    {
        SsoConnectionShowingActionEvent::dispatch($connection);

        $connection->loadMissing('organization')->loadCount('identities');

        SsoConnectionShownActionEvent::dispatch($connection);

        return $connection;
    }
}
