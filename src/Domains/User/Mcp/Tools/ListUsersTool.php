<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\User\Data\ListUsersData;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\ListUsersMcpRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

#[Description('List users, oldest first. Filter by status or search name, email and display name. Paginated.')]
final class ListUsersTool extends Tool
{
    public function handle(ListUsersMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Matches name, email or display name.'),
            'status' => $schema->string()->description('active, pending, suspended or deactivated.'),
            'trashed' => $schema->string()->description('only: deleted users only; with: deleted ones too. Needs a soft-deleting user model.'),
        ] + ListUsersData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserResource::collectionSchema($schema);
    }
}
