<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\SyncOrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Support\Statuses;

#[Description('Create or update one organization from its record in an external system (ERP, CRM, ...), matched by source + external_id. Only the fields given are written; the result says created, updated or unchanged. A new organization has no owner unless one is given.')]
final class SyncOrganizationTool extends Tool
{
    public function handle(SyncOrganizationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'source' => $schema->string()->description('The external system, lower case, e.g. erp or crm.')->required(),
            'external_id' => $schema->string()->description('The organization\'s id in that system.')->required(),
            'account_number' => $schema->string()->description('Its account number in that system.'),
            'name' => $schema->string()->description('Required when the record creates a new organization.'),
            'slug' => $schema->string()->description('URL slug; generated from the name when creating if left out.'),
            'domains' => $schema->array()->items($schema->string())->description('Email domains it owns. Replaces the current list.'),
            'auto_join' => $schema->boolean()->description('Let verified users on its domains join automatically.'),
            'owner' => $schema->string()->description('Owner user id; applied only when the organization has no owner yet.'),
            'organization' => $schema->string()->description('On the first sync, the slug of an existing organization to link instead of creating one.'),
            'status' => $schema->string()->enum(Statuses::values(Statuses::organizations()))->description('The organization\'s status in that system.'),
            'status_reason' => $schema->string()->description('Why it has that status.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::structuredSchema($schema) + ['outcome' => $schema->string()->enum([OrganizationSyncResult::CREATED, OrganizationSyncResult::UPDATED, OrganizationSyncResult::UNCHANGED])->required()];
    }
}
