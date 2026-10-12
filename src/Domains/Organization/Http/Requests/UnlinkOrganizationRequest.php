<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\UnlinkOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;

final class UnlinkOrganizationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.organizations.update';
    }

    public function persist(): JsonResponse
    {
        return (new OrganizationResource(app(UnlinkOrganizationAction::class)->execute($this->organization(), (string) $this->route('source'))))->response();
    }
}
