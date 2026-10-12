<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class ConfirmImportMcpRequest extends TransferMcpRequest
{
    /**
     * Confirming applies the rows as the confirmer, so it always needs the
     * import's permission - starting the import is not enough.
     */
    protected function self(): ?Model
    {
        return null;
    }

    protected function handle(array $validated): Response|ResponseFactory
    {
        if (! $this->transfer()->type->isImport()) {
            return Response::error('Not found.');
        }

        return (new TransferResource(app(ConfirmImportAction::class)->execute($this->transfer(), $this->actor())))->signed()->toStructuredResponse();
    }
}
