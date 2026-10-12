<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\LinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\LinkOrganizationData;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class LinkOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['source' => $this->route('source')]);
    }

    public function rules(): array
    {
        return LinkOrganizationData::rules();
    }

    public function persist(): JsonResponse
    {
        app(LinkOrganizationAction::class)->execute($this->organization(), LinkOrganizationData::fromValidated($this->validated()));

        return (new OrganizationResource($this->organization()->refresh()->load(['domains', 'links'])))->response();
    }
}
