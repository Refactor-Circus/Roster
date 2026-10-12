<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Requests\ShowTransferMcpRequest;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

#[Description('Show an import or export with its per-row report, 100 rows per page (rows_page). A finished export includes a download_url valid for 15 minutes.')]
final class ShowTransferTool extends Tool
{
    public function handle(ShowTransferMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'transfer' => $schema->string()->description('The transfer id.')->required(),
            'rows_page' => $schema->integer()->description('Page of rows, 100 per page, from 1.')->min(1),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TransferResource::structuredSchema($schema);
    }
}
