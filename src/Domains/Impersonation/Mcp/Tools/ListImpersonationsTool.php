<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Impersonation\Data\ListImpersonationsData;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests\ListImpersonationsMcpRequest;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;

#[Description('List impersonations, newest first: who acted as whom, why, and when it ended.')]
final class ListImpersonationsTool extends Tool
{
    public function handle(ListImpersonationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'active' => $schema->boolean()->description('Only impersonations in progress.'),
            'user' => $schema->string()->description('Only those acting as this user id.'),
            'impersonator' => $schema->string()->description('Only those by this user id.'),
            'organization' => $schema->string()->description('An organization slug.'),
        ] + ListImpersonationsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ImpersonationResource::collectionSchema($schema);
    }
}
