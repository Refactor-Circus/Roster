<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Transfer\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Requests\StartImportMcpRequest;
use RefactorCircus\Roster\Domains\Transfer\Resources\TransferResource;

#[Description('Import a CSV of members, users or teams. Nothing changes yet: every row is checked and a preview is returned (create, link, invite, update, skip or error per row). Review it with show-transfer-tool, then apply it with confirm-import-tool. Members and teams imports need an organization. A password column is refused.')]
final class StartImportTool extends Tool
{
    public function handle(StartImportMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'type' => $schema->string()->description('import_members, import_users or import_teams.')->required(),
            'organization' => $schema->string()->description('An organization slug; required for import_members and import_teams.'),
            'content' => $schema->string()->description('The CSV text, with a header row.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TransferResource::structuredSchema($schema);
    }
}
