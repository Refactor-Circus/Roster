<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\ShowOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class ShowOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationResource(app(ShowOrganizationAction::class)->execute($this->organization())))->toStructuredResponse();
    }
}
