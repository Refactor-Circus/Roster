<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Policies;

use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;
use RefactorCircus\Roster\Support\Policies\Policy;

/**
 * An import or export needs the permission its type names - managing members
 * for a member import, viewing users for a user export - in its organization,
 * to see it, download it, confirm it or cancel it.
 */
class TransferPolicy extends Policy
{
    public function view(?Model $user, TransferModel $transfer): bool
    {
        return $this->permits($user, $transfer->type->permission(), $transfer);
    }

    public function manage(?Model $user, TransferModel $transfer): bool
    {
        return $this->permits($user, $transfer->type->permission(), $transfer);
    }
}
