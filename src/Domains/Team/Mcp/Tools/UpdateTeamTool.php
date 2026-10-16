<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\UpdateTeamMcpRequest;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesTeam;
use RefactorCircus\Roster\Support\Statuses;

#[Description('Rename a team or change its slug. Only the fields given change.')]
final class UpdateTeamTool extends Tool
{
    use DescribesTeam;

    public function handle(UpdateTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->teamSchema($schema) + [
            'name' => $schema->string()->description('Display name.'),
            'slug' => $schema->string()->description('New slug, unique within the organization.'),
        ] + $this->statusSchema($schema, Statuses::teams(), 'team');
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TeamResource::structuredSchema($schema);
    }
}
