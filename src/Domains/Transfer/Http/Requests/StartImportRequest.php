<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class StartImportRequest extends StartTransferRequest
{
    protected function fallbackAbility(): string
    {
        return 'roster.users.create';
    }

    public function rules(): array
    {
        return StartImportData::rules();
    }

    public function persist(): JsonResponse
    {
        return (new TransferResource(app(StartImportAction::class)->execute(StartImportData::fromValidated($this->validated()), $this->requester())))->response()->setStatusCode(202);
    }

    private function requester(): Model
    {
        return $this->actor() ?? abort(401);
    }
}
