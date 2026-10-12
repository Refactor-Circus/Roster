<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\SyncOrganizationsMcpRequest;

#[Description('Sync a batch of external records (each with the fields of sync-organization-tool). Each record succeeds or fails on its own; the result lists created, updated, unchanged or error (with messages) per record, in order.')]
final class SyncOrganizationsTool extends Tool
{
    public function handle(SyncOrganizationsMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'records' => $schema->array()->items($schema->object())->description('Records, each {source, external_id, name?, account_number?, slug?, domains?, auto_join?, owner?, organization?}.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return [
            'data' => $schema->array()->items($schema->object([
                'source' => $schema->string()->nullable()->required(),
                'external_id' => $schema->string()->nullable()->required(),
                'outcome' => $schema->string()->enum([OrganizationSyncResult::CREATED, OrganizationSyncResult::UPDATED, OrganizationSyncResult::UNCHANGED, SyncOrganizationsAction::ERROR])->required(),
                'organization' => $schema->string()->nullable()->description('Organization slug, unless the record failed.')->required(),
                'errors' => $schema->object()->nullable()->description('field => messages, when the record failed.')->required(),
            ]))->description('One result per record, in order.')->required(),
        ];
    }
}
