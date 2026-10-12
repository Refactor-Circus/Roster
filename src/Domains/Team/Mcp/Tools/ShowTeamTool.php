<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\ShowTeamMcpRequest;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesTeam;

#[Description('Show a team with its members.')]
final class ShowTeamTool extends Tool
{
    use DescribesTeam;

    public function handle(ShowTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TeamResource::structuredSchema($schema);
    }
}
