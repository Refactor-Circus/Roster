<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Requests\ConfirmImportMcpRequest;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

#[Description('Apply an import that is awaiting confirmation. Rows are applied as you, with your permissions checked again; errors are reported per row.')]
final class ConfirmImportTool extends Tool
{
    public function handle(ConfirmImportMcpRequest $request): Response|ResponseFactory
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
