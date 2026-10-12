<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\AddTeamMemberMcpRequest;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesTeam;

#[Description('Seat an organization member on a team. The user must already belong to the organization.')]
final class AddTeamMemberTool extends Tool
{
    use DescribesTeam;

    public function handle(AddTeamMemberMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema) + [
            'user' => $schema->string()->description('The user id to seat.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TeamResource::structuredSchema($schema);
    }
}
