<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Sso\Mcp\Requests\ShowSsoConnectionMcpRequest;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

#[Description('Show an SSO connection with its callback and metadata URLs. Secrets are never returned.')]
final class ShowSsoConnectionTool extends Tool
{
    public function handle(ShowSsoConnectionMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'connection' => $schema->string()->description('The connection slug.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return SsoConnectionResource::structuredSchema($schema);
    }
}
