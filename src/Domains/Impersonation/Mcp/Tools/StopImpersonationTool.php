<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests\StopImpersonationMcpRequest;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;

#[Description('End an impersonation (or cancel an unused link). Ending someone else\'s needs roster.users.impersonate; their browser switches back on its next request.')]
final class StopImpersonationTool extends Tool
{
    public function handle(StopImpersonationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'impersonation' => $schema->string()->description('The impersonation id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ImpersonationResource::structuredSchema($schema);
    }
}
