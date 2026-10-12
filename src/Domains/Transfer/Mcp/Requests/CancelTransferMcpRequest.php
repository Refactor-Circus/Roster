<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\CancelTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class CancelTransferMcpRequest extends TransferMcpRequest
{
    protected function handle(array $validated): ResponseFactory
    {
        return (new TransferResource(app(CancelTransferAction::class)->execute($this->transfer())))->signed()->toStructuredResponse();
    }
}
