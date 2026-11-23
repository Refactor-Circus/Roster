<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\User\Http\Resources\UserStatsResource;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\ShowUserStatsMcpRequest;

#[Description('Count users: the total, and how many hold each status (users without a profile count as active). Refreshed at most once a minute, and whenever a user changes.')]
final class ShowUserStatsTool extends Tool
{
    public function handle(ShowUserStatsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserStatsResource::structuredSchema($schema);
    }
}
