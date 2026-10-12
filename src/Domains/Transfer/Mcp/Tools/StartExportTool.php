<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Requests\StartExportMcpRequest;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

#[Description('Export members (of an organization), users or organizations (in the import_organizations columns, so an edited file imports back) as CSV in the background. Check progress with show-transfer-tool, which returns a short-lived download_url when it is ready.')]
final class StartExportTool extends Tool
{
    public function handle(StartExportMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('export_members, export_users or export_organizations.')->required(),
            'organization' => $schema->string()->description('An organization slug; required for export_members.'),
            'filters' => $schema->object([
                'external_source' => $schema->string()->description('export_organizations only: just records from this external system, e.g. erp.'),
            ])->description('external_source, for organizations.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TransferResource::structuredSchema($schema);
    }
}
