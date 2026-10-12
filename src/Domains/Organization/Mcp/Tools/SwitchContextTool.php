<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\SwitchContextMcpRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Set a user\'s current organization and, optionally, team. The user must belong to both.')]
final class SwitchContextTool extends Tool
{
    use DescribesUser;

    public function handle(SwitchContextMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'organization' => $schema->string()->description('The organization slug.')->required(),
            'team' => $schema->string()->description('A team slug in that organization. Omit or null for no team.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserResource::structuredSchema($schema);
    }
}
