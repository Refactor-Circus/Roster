<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Sso\Mcp\Requests\CreateSsoConnectionMcpRequest;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;
use RefactorCircus\Roster\Mcp\Concerns\DescribesSsoConnection;

#[Description('Connect an organization to its identity provider. protocol is oidc (issuer, client_id, client_secret), azure for Microsoft Entra ID (tenant, client_id, client_secret) or saml (metadata_url, or entity_id + sso_url + certificate). Returns the callback URL to register at the provider.')]
final class CreateSsoConnectionTool extends Tool
{
    use DescribesOrganization;
    use DescribesSsoConnection;

    public function handle(CreateSsoConnectionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'name' => $schema->string()->description('Display name, e.g. Okta.')->required(),
            'slug' => $schema->string()->description('URL identifier. Omit to generate one.'),
            'protocol' => $schema->string()->description('oidc, azure or saml.')->required(),
        ] + $this->ssoConnectionSettingsSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SsoConnectionResource::structuredSchema($schema);
    }
}
