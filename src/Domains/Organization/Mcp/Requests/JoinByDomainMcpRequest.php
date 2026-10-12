<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;

final class JoinByDomainMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function handle(array $validated): ResponseFactory
    {
        $organizations = app(JoinOrganizationsByDomainAction::class)->execute($this->targetUser());

        return OrganizationResource::collection($organizations)->toStructuredResponse();
    }
}
