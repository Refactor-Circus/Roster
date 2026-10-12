<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class UpdateOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + UpdateOrganizationData::rules($this->organization());
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new OrganizationResource(app(UpdateOrganizationAction::class)->execute($this->organization(), UpdateOrganizationData::fromValidated($validated))))->toStructuredResponse();
    }
}
