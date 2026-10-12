<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Role\Mcp\Requests\AssignRoleMcpRequest;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Give a user a role: a global role with no organization; an organization role with organization (the user must be a member); a team role with organization and team (the user must sit on it). You can only assign roles whose permissions you hold.')]
final class AssignRoleTool extends Tool
{
    use DescribesUser;

    public function handle(AssignRoleMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'role' => $schema->string()->description('The role id.')->required(),
            'organization' => $schema->string()->description('An organization slug.'),
            'team' => $schema->string()->description('A team slug in the organization, for team roles.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return RoleAssignmentResource::structuredSchema($schema);
    }
}
