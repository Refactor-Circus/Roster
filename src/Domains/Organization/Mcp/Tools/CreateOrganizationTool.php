<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\CreateOrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Create an organization. The owner becomes its first member.')]
final class CreateOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'name' => $schema->string()->description('Display name.')->required(),
            'slug' => $schema->string()->description('URL identifier (letters, numbers, dashes). Omit to generate one from the name.'),
            'owner' => $schema->string()->description('The owning user id. Optional: an organization may have no owner until ownership is transferred to a member.'),
        ] + $this->organizationSettingsSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::structuredSchema($schema);
    }
}
