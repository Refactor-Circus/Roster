<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\ShowOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class ShowOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.view';
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(ShowOrganizationAction::class)->execute($this->organization())))->response();
    }
}
