<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\ListOrganizationsAction;
use RefactorCircus\Roster\Domains\Organization\Data\ListOrganizationsData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Http\Request;

final class IndexOrganizationsRequest extends Request
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
        return ListOrganizationsData::rules();
    }

    public function persist(): JsonResponse
    {
        return OrganizationResource::collection(app(ListOrganizationsAction::class)->execute(ListOrganizationsData::fromValidated($this->validated()), $this->organizations()))->response();
    }
}
