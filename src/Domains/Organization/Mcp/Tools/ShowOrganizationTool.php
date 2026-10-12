<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\ShowOrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Show an organization with its domains and member and team counts.')]
final class ShowOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(ShowOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::structuredSchema($schema);
    }
}
