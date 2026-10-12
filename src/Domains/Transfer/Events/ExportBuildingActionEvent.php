<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

/**
 * An export is about to start writing its file.
 */
final class ExportBuildingActionEvent implements ActionStartingEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TransferModel $transfer,
    ) {}
}
