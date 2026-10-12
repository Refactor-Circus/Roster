<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Transfer\Actions\CancelTransferAction;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class CancelTransferRequest extends TransferRequest
{
    public function persist(): JsonResponse
    {
        return (new TransferResource(app(CancelTransferAction::class)->execute($this->transfer())))->response();
    }
}
