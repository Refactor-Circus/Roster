<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Roster\Domains\Scim\Mcp\Requests\ListScimTokensMcpRequest;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('List an organization\'s SCIM provisioning tokens (never the token values) with when each was last used.')]
final class ListScimTokensTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListScimTokensMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
        ] + ListScimTokensData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ScimTokenResource::collectionSchema($schema);
    }
}
