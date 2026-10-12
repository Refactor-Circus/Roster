<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Mcp\Requests\ListPermissionsMcpRequest;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;

#[Description('List every permission name roles can grant, alphabetically. Paginated.')]
final class ListPermissionsTool extends Tool
{
    public function handle(ListPermissionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Matches part of the name.'),
        ] + ListPermissionsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return PermissionResource::collectionSchema($schema);
    }
}
