<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\RemoveTeamMemberMcpRequest;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesTeam;

#[Description('Remove a user from a team. They stay in the organization.')]
final class RemoveTeamMemberTool extends Tool
{
    use DescribesTeam;

    public function handle(RemoveTeamMemberMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema) + [
            'user' => $schema->string()->description('The user id, as returned by list-users-tool.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TeamResource::structuredSchema($schema);
    }
}
