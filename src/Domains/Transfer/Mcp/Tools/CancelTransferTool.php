<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Requests\CancelTransferMcpRequest;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

#[Description('Cancel an import or export that has not finished. An import awaiting confirmation is dropped with nothing applied.')]
final class CancelTransferTool extends Tool
{
    public function handle(CancelTransferMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'transfer' => $schema->string()->description('The transfer id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TransferResource::structuredSchema($schema);
    }
}
