<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

/**
 * The TransferModel `saving` Eloquent event.
 */
final class TransferSavingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TransferModel $transfer) {}

    public function model(): Model
    {
        return $this->transfer;
    }

    public function hook(): string
    {
        return 'saving';
    }
}
