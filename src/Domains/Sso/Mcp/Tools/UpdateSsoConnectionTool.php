<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Sso\Mcp\Requests\UpdateSsoConnectionMcpRequest;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesSsoConnection;

#[Description('Change an SSO connection. The protocol cannot change; omit client_secret to keep it.')]
final class UpdateSsoConnectionTool extends Tool
{
    use DescribesSsoConnection;

    public function handle(UpdateSsoConnectionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'connection' => $schema->string()->description('The connection slug.')->required(),
            'name' => $schema->string()->description('Display name.'),
            'slug' => $schema->string()->description('New URL identifier.'),
        ] + $this->ssoConnectionSettingsSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SsoConnectionResource::structuredSchema($schema);
    }
}
