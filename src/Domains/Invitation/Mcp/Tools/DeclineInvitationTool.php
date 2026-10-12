<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Requests\DeclineInvitationMcpRequest;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;

#[Description('Decline an invitation as the authenticated user, whose email must match the invitation.')]
final class DeclineInvitationTool extends Tool
{
    public function handle(DeclineInvitationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'token' => $schema->string()->description('The token from the invitation link.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return InvitationResource::structuredSchema($schema);
    }
}
