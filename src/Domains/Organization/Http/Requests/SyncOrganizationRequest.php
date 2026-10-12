<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\OrganizationSyncResult;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Http\Request;

/**
 * Upsert one organization by its record in an external system, named in
 * the URL: `PUT roster/organizations/external/{source}/{externalId}`.
 */
final class SyncOrganizationRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.organizations.sync';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['source' => $this->route('source'), 'external_id' => $this->route('externalId')]);
    }

    public function rules(): array
    {
        return SyncOrganizationData::rules();
    }

    public function persist(): JsonResponse
    {
        $result = app(SyncOrganizationAction::class)->execute(SyncOrganizationData::fromValidated($this->validated()));

        return (new OrganizationResource($result->organization))
            ->additional(['outcome' => $result->outcome])
            ->response()
            ->setStatusCode($result->outcome === OrganizationSyncResult::CREATED ? 201 : 200);
    }
}
