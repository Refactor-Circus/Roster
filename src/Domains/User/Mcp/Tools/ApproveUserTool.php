<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\ApproveUserMcpRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Approve an account awaiting approval (status pending): it becomes active. Only pending accounts can be approved.')]
final class ApproveUserTool extends Tool
{
    use DescribesUser;

    public function handle(ApproveUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserResource::structuredSchema($schema);
    }
}
