<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Requests\CreatePermissionMcpRequest;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;

#[Description('Declare an app permission, e.g. invoices.edit, for roles to grant. Lower-case, dot-separated.')]
final class CreatePermissionTool extends Tool
{
    public function handle(CreatePermissionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Permission name, e.g. invoices.edit. Permanent.')->required(),
            'description' => $schema->string()->description('What it allows.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return PermissionResource::structuredSchema($schema);
    }
}
