<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Mcp\Request;

final class ListOrganizationsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    protected function rules(): array
    {
        return ListOrganizationsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $organizations = app(ListOrganizationsAction::class)->execute(ListOrganizationsData::fromValidated($validated), $this->organizations());

        return OrganizationResource::collection($organizations)->toStructuredResponse();
    }
}
