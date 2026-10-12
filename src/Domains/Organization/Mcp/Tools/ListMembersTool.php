<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Data\ListMembersData;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\ListMembersMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('List an organization\'s members, oldest first, with how they joined and their teams, optionally only one team\'s. Paginated.')]
final class ListMembersTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListMembersMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'team' => $schema->string()->description('Only the members seated on the team with this slug.'),
        ] + ListMembersData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return MemberResource::collectionSchema($schema);
    }
}
