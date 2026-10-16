<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\ListOrganizationsMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Support\Statuses;

#[Description('List organizations by name. Filter by a search term, a member user, or an external record (source, external_id, account_number). Paginated.')]
final class ListOrganizationsTool extends Tool
{
    public function handle(ListOrganizationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'search' => $schema->string()->description('Matches name or slug.'),
            'trashed' => $schema->string()->description('only: deleted organizations only; with: deleted ones too.'),
            'user' => $schema->string()->description('Only organizations this user id belongs to.'),
            'source' => $schema->string()->description('Only organizations linked to this external system.'),
            'external_id' => $schema->string()->description('Only organizations with this id in an external system.'),
            'account_number' => $schema->string()->description('Only organizations with this account number in an external system.'),
            'status' => $schema->string()->enum(Statuses::values(Statuses::organizations()))->description('Only organizations with this status.'),
        ] + ListOrganizationsData::pagination()->schema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::collectionSchema($schema);
    }
}
