<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

/**
 * Works on deleted records too.
 */
final class RestoreOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashedOrganization();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationResource(app(RestoreOrganizationAction::class)->execute($this->trashedOrganization())))->toStructuredResponse();
    }
}
