<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\RestoreOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

/**
 * Works on deleted organizations too.
 */
final class RestoreOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.delete';
    }

    protected function scope(): OrganizationModel
    {
        return $this->trashedOrganization();
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(RestoreOrganizationAction::class)->execute($this->trashedOrganization())))->response();
    }
}
