<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\SearchOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\SearchOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Http\Request;

final class SearchOrganizationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return SearchOrganizationsData::rules();
    }

    public function persist(): JsonResponse
    {
        return OrganizationResource::collection(app(SearchOrganizationsAction::class)->execute(SearchOrganizationsData::fromValidated($this->validated()), $this->organizations()))->response();
    }
}
