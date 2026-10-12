<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Mcp\Requests\ListRoleAssignmentsMcpRequest;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('List the roles a user holds, with the organization or team each applies in.')]
final class ListRoleAssignmentsTool extends Tool
{
    use DescribesUser;

    public function handle(ListRoleAssignmentsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'organization' => $schema->string()->description('An organization slug.'),
        ] + ListRoleAssignmentsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleAssignmentResource::collectionSchema($schema);
    }
}
