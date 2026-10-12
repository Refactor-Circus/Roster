<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\ListRolesAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRolesData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Scopes;

final class IndexRolesRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    protected function acrossOrganizations(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return ListRolesData::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleResource::collection(app(ListRolesAction::class)->execute(ListRolesData::fromValidated($this->validated()), $this->organizations()))->response();
    }
}
