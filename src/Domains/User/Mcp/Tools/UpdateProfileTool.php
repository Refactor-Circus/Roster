<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UpdateProfileMcpRequest;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesProfilePayload;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Update a user\'s profile. Only the fields given change; the profile is created if missing.')]
final class UpdateProfileTool extends Tool
{
    use DescribesProfilePayload;
    use DescribesUser;

    public function handle(UpdateProfileMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema) + $this->profileSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return UserResource::structuredSchema($schema);
    }
}
