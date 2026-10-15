<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\CreateTeamMcpRequest;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;
use RefactorCircus\Roster\Support\Statuses;

#[Description('Create a team inside an organization.')]
final class CreateTeamTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL identifier, unique within the organization. Omit to generate one.'),
        ] + $this->statusSchema($schema, Statuses::teams(), 'team');
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TeamResource::structuredSchema($schema);
    }
}
