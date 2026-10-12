<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\UpdateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\UpdateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class UpdateOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    public function rules(): array
    {
        return UpdateOrganizationData::rules($this->organization());
    }

    public function persist(): JsonResponse
    {
        $organization = app(UpdateOrganizationAction::class)->execute($this->organization(), UpdateOrganizationData::fromValidated($this->validated()));

        return (new OrganizationResource($organization))->response();
    }
}
