<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Actions;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Impex\Domains\Run\Models\RunModel;
use RefactorCircus\Impex\Impex;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferStatus;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferCancelledActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Events\TransferCancellingActionEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Domains\Transfer\Services\Transfers;

final class CancelTransferAction
{
    public function __construct(private readonly Transfers $transfers) {}

    /**
     * Cancel an import awaiting confirmation (nothing was applied), or an
     * import or export still running (rows already applied stay).
     */
    public function execute(TransferModel $transfer): TransferModel
    {
        $this->transfers->ensureAvailable();

        if ($transfer->status->isFinished()) {
            throw ValidationException::withMessages(['transfer' => __('roster::roster.transfer_finished')]);
        }

        TransferCancellingActionEvent::dispatch($transfer);

        $run = RunModel::query()->find($transfer->impex_run_id);
        $impex = app(Impex::class);

        if ($run instanceof RunModel && $transfer->status === TransferStatus::AwaitingConfirmation) {
            $impex->signal($run, 'confirm', ['confirmed' => false, 'reason' => 'cancelled']);
        } elseif ($run instanceof RunModel) {
            $impex->cancel($run, 'Cancelled in Roster.');
        }

        // Impex settles the run on its own; Roster's write is only the
        // transfer's status, if the run has not already closed it.
        $transfer = DB::transaction(function () use ($transfer): TransferModel {
            $transfer->refresh();

            if (! $transfer->status->isFinished()) {
                $transfer->update(['status' => TransferStatus::Cancelled, 'finished_at' => now()]);
            }

            return $transfer->refresh();
        });

        TransferCancelledActionEvent::dispatch($transfer);

        return $transfer;
    }
}
