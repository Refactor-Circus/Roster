<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Tools;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use Laravel\Mcp\Server\Attributes\Description;
use RefactorCircus\Keystone\Mcp\Tool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\AddMemberMcpRequest;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;
use RefactorCircus\Roster\Mcp\Concerns\DescribesOrganization;

#[Description('Add an existing user to an organization.')]
final class AddMemberTool extends Tool
{
    use DescribesOrganization;

    public function handle(AddMemberMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array
    {
        return $this->organizationSchema($schema) + [
            'user' => $schema->string()->description('The user id to add.')->required(),
        ];
    }

    public function outputSchema(JsonSchema $schema): array
    {
        return MemberResource::structuredSchema($schema);
    }
}
