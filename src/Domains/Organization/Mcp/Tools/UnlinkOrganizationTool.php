<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\UnlinkOrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Remove an organization\'s link to one external system.')]
final class UnlinkOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(UnlinkOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'source' => $schema->string()->description('The external system to unlink.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::structuredSchema($schema);
    }
}
