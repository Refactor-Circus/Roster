<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

/**
 * Confirming applies the rows as the confirmer, so it always needs the
 * import's permission - starting the import is not enough.
 */
final class ConfirmImportRequest extends TransferRequest
{
    protected function self(): ?Model
    {
        return null;
    }

    public function persist(): JsonResponse
    {
        abort_unless($this->transfer()->type->isImport(), 404);

        return (new TransferResource(app(ConfirmImportAction::class)->execute($this->transfer(), $this->actor())))->response();
    }
}
