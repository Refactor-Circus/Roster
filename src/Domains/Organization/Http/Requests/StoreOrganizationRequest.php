<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Http\Request;

final class StoreOrganizationRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.create';
    }

    public function rules(): array
    {
        return CreateOrganizationData::rules();
    }

    public function persist(): JsonResponse
    {
        $organization = app(CreateOrganizationAction::class)->execute(CreateOrganizationData::fromValidated($this->validated()));

        return (new OrganizationResource($organization))->response()->setStatusCode(201);
    }
}
