<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Policies;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferRowModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * A row of an import follows its transfer, through whichever policy is
 * registered for TransferModel.
 */
class TransferRowPolicy extends Policy
{
    public function view(?Model $user, TransferRowModel $row): bool
    {
        // Guests too: the transfer's policy decides, as it does with authorization off.
        return Gate::forUser($user)->allows('view', $row->transfer);
    }
}
