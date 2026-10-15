<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Team\Data\ListTeamsData;
use RefactorCircus\Roster\Domains\Team\Mcp\Requests\ListTeamsMcpRequest;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;
use RefactorCircus\Roster\Support\Statuses;

#[Description('List an organization\'s teams by name, with member counts. Paginated.')]
final class ListTeamsTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListTeamsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'status' => $schema->string()->enum(Statuses::values(Statuses::teams()))->description('Only teams with this status.'),
        ] + ListTeamsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return TeamResource::collectionSchema($schema);
    }
}
