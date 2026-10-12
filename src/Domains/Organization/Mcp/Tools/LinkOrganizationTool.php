<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\LinkOrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Record or change an organization\'s id and account number in one external system. An id already linked to another organization is refused.')]
final class LinkOrganizationTool extends Tool
{
    use DescribesOrganization;

    public function handle(LinkOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'source' => $schema->string()->description('The external system, lower case, e.g. erp or crm.')->required(),
            'external_id' => $schema->string()->description('The organization\'s id in that system.')->required(),
            'account_number' => $schema->string()->description('Its account number in that system.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::structuredSchema($schema);
    }
}
