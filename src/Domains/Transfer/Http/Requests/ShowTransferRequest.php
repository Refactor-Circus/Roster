<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Transfer\Actions\ShowTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class ShowTransferRequest extends TransferRequest
{
    public function rules(): array
    {
        return [
            'rows_page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function persist(): JsonResponse
    {
        return TransferResource::make(app(ShowTransferAction::class)->execute($this->transfer()))->withRows($this->integer('rows_page', 1))->response();
    }
}
