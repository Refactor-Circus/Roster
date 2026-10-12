<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Scim\Mcp\Requests\CreateScimTokenMcpRequest;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Issue a SCIM token for an identity provider to provision the organization. The token is returned once; hand it to the operator configuring the identity provider, along with base_url.')]
final class CreateScimTokenTool extends Tool
{
    use DescribesOrganization;

    public function handle(CreateScimTokenMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'name' => $schema->string()->description('What uses it, e.g. Okta provisioning.')->required(),
            'expires_in_days' => $schema->integer()->description('Expire after this many days. Omit for no expiry.')->min(1),
            'sso_connection' => $schema->string()->description('An SSO connection slug: provisioned users\' externalIds become its SSO subjects.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ScimTokenResource::structuredSchema($schema) + ['token' => $schema->string()->description('The token itself. Shown only this once; give it to the identity provider.')->required()];
    }
}
