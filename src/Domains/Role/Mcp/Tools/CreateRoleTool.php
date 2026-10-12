<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Role\Mcp\Requests\CreateRoleMcpRequest;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;

#[Description('Create a role. scope is global, organization or team. Give organization to make it that organization\'s own; omit to share it with every organization. You can only grant permissions you hold.')]
final class CreateRoleTool extends Tool
{
    public function handle(CreateRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('Identifier. Omit to generate one.'),
            'scope' => $schema->string()->description('global, organization or team.')->required(),
            'organization' => $schema->string()->description('An organization slug.'),
            'description' => $schema->string()->description('What the role is for.'),
            'permissions' => $schema->array()->items($schema->string())->description('Permission names to grant.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::structuredSchema($schema);
    }
}
