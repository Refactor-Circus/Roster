<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use RefactorCircus\Roster\Domains\Transfer\Events\TransferShowingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferShownActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

final class ShowTransferAction
{
    public function execute(TransferModel $transfer): TransferModel
    {
        TransferShowingActionEvent::dispatch($transfer);

        $transfer->loadMissing(['organization', 'requester']);

        TransferShownActionEvent::dispatch($transfer);

        return $transfer;
    }
}
