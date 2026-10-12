<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Request;

final class CreateOrganizationMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.create';
    }

    protected function rules(): array
    {
        return CreateOrganizationData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationResource(app(CreateOrganizationAction::class)->execute(CreateOrganizationData::fromValidated($validated))))->toStructuredResponse();
    }
}
