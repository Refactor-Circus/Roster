<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionStartingEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

/**
 * One row of a confirmed import is about to be applied.
 */
final class ImportRowApplyingActionEvent implements ActionStartingEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TransferModel $transfer,
        public int $line,
        /** @var array<string, string> */
        public array $values,
    ) {}
}
