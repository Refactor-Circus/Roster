<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Role\Mcp\Requests\ListRolesMcpRequest;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;

#[Description('List roles. With organization, the roles usable there (shared ones plus its own). Filter by scope. Paginated.')]
final class ListRolesTool extends Tool
{
    public function handle(ListRolesMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'scope' => $schema->string()->description('global, organization or team.'),
            'organization' => $schema->string()->description('An organization slug.'),
        ] + ListRolesData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleResource::collectionSchema($schema);
    }
}
