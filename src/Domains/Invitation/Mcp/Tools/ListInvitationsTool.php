<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Invitation\Data\ListInvitationsData;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Requests\ListInvitationsMcpRequest;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('List an organization\'s invitations, newest first. Paginated.')]
final class ListInvitationsTool extends Tool
{
    use DescribesOrganization;

    public function handle(ListInvitationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'status' => $schema->string()->description('pending, accepted, declined, revoked or expired.'),
        ] + ListInvitationsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return InvitationResource::collectionSchema($schema);
    }
}
