<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\RejectUserMcpRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Reject an account awaiting approval: it is deactivated with the reason (kept, not deleted), and can be reactivated later.')]
final class RejectUserTool extends Tool
{
    use DescribesUser;

    public function handle(RejectUserMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + [
            'reason' => $schema->string()->description('Why, recorded on the profile and told to the user.'),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserResource::structuredSchema($schema);
    }
}
