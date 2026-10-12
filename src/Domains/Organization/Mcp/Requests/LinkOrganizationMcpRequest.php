<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\LinkOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class LinkOrganizationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + LinkOrganizationData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        app(LinkOrganizationAction::class)->execute($this->organization(), LinkOrganizationData::fromValidated($validated));

        return (new OrganizationResource($this->organization()->refresh()->load(['domains', 'links'])))->toStructuredResponse();
    }
}
