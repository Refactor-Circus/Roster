<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Role\Mcp\Requests\ShowRoleMcpRequest;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;

#[Description('Show a role with its permissions.')]
final class ShowRoleTool extends Tool
{
    public function handle(ShowRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'role' => $schema->string()->description('The role id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::structuredSchema($schema);
    }
}
