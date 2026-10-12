<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use RefactorCircus\Keystone\Contracts\ModelLifecycleEvent;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;

/**
 * The TransferRowModel `updating` Eloquent event.
 */
final class TransferRowUpdatingEvent implements ModelLifecycleEvent
{
    use Dispatchable;
    use SerializesModels;

    public function __construct(public TransferRowModel $transferRow) {}

    public function model(): Model
    {
        return $this->transferRow;
    }

    public function hook(): string
    {
        return 'updating';
    }
}
