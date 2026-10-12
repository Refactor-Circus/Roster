<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\JoinByDomainMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesUser;

#[Description('Join a user to every auto-join organization that owns their email\'s domain. Does nothing unless their email is verified. Returns the organizations newly joined.')]
final class JoinByDomainTool extends Tool
{
    use DescribesUser;

    public function handle(JoinByDomainMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->userSchema($schema);
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return OrganizationResource::collectionSchema($schema);
    }
}
