<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Requests;

use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartExportData;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

final class StartExportMcpRequest extends StartTransferMcpRequest
{
    protected function fallbackAbility(): string
    {
        return 'roster.users.view';
    }

    protected function rules(): array
    {
        return StartExportData::rules();
    }

    protected function handle(array $validated): Response|ResponseFactory
    {
        $actor = $this->actor();

        if ($actor === null) {
            return Response::error('Unauthorized.');
        }

        return (new TransferResource(app(StartExportAction::class)->execute(StartExportData::fromValidated($validated), $actor)))->toStructuredResponse();
    }
}
