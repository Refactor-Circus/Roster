<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class ShowTransferMcpRequest extends TransferMcpRequest
{
    protected function rules(): array
    {
        return [
            'rows_page' => ['sometimes', 'integer', 'min:1'],
            'transfer' => ['required', 'string'],
        ];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return TransferResource::make(app(ShowTransferAction::class)->execute($this->transfer()))->withRows((int) ($validated['rows_page'] ?? 1))->signed()->toStructuredResponse();
    }
}
