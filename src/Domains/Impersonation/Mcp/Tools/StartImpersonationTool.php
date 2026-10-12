<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Requests\StartImpersonationMcpRequest;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Issue a one-time link (valid for a few minutes) that lets you act as another user in your browser. It only works in a browser signed in as you, needs a reason, and is fully audited. You cannot impersonate yourself, inactive users, super-admins (unless you are one), or anyone with permissions you lack. Give the link to the human operator; do not open it yourself.')]
final class StartImpersonationTool extends Tool
{
    use DescribesUser;

    public function handle(StartImpersonationMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'reason' => $schema->string()->description('Why, e.g. a support ticket number. Recorded in the audit log.')->required(),
            'organization' => $schema->string()->description('Impersonate within this organization (its members only).'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return ImpersonationResource::structuredSchema($schema) + ['url' => $schema->string()->description('The one-time link to open in the impersonator\'s signed-in browser.')->required()];
    }
}
