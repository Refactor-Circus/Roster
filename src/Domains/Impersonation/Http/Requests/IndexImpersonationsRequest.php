<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Impersonation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Impersonation\Actions\ListImpersonationsAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\ListImpersonationsData;
use RefactorCircus\Roster\Domains\Impersonation\Resources\ImpersonationResource;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Scopes;

final class IndexImpersonationsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.impersonate';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ListImpersonationsData::rules();
    }

    public function persist(): JsonResponse
    {
        return ImpersonationResource::collection(app(ListImpersonationsAction::class)->execute(ListImpersonationsData::fromValidated($this->validated()), $this->organizations()))->response();
    }
}
