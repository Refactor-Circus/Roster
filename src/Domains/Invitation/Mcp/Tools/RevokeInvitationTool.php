<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Requests\RevokeInvitationMcpRequest;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Revoke a pending invitation so its link stops working.')]
final class RevokeInvitationTool extends Tool
{
    use DescribesOrganization;

    public function handle(RevokeInvitationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'invitation' => $schema->string()->description('The invitation id.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return InvitationResource::structuredSchema($schema);
    }
}
