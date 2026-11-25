<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Http\Resources\OrganizationStatsResource;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\ShowOrganizationStatsMcpRequest;

#[Description('Count organizations (deleted ones left out), teams and pending invitations.')]
final class ShowOrganizationStatsTool extends Tool
{
    public function handle(ShowOrganizationStatsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationStatsResource::structuredSchema($schema);
    }
}
