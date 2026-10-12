<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Keystone\Contracts\UnauditedActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

/**
 * One row of a confirmed import has been applied; `outcome` holds what it
 * did, or why it was skipped or failed.
 */
final class ImportRowAppliedActionEvent implements ActionFinishedEvent, UnauditedActionEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(
        public TransferModel $transfer,
        public int $line,
        /** @var array{action: string, reasons: array<int, string>} */
        public array $outcome,
    ) {}
}
