<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Requests\ListUserPermissionsMcpRequest;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('The permissions a user effectively holds: globally, or in an organization or one of its teams. Includes whether they are a super-admin.')]
final class ListUserPermissionsTool extends Tool
{
    use DescribesUser;

    public function handle(ListUserPermissionsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'organization' => $schema->string()->description('An organization slug.'),
            'team' => $schema->string()->description('A team slug in the organization.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->object([
                'super_admin' => $schema->boolean()->required(),
                'permissions' => $schema->array()->items($schema->string())->description('Permission names the user holds in the scope asked about.')->required(),
            ])->required(),
        ];
    }
}
