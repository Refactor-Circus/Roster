<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\CreateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\CreateRoleData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Http\Request;
use RefactorCircus\Roster\Support\Scopes;

final class StoreRoleRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    public function rules(): array
    {
        return CreateRoleData::rules();
    }

    public function persist(): JsonResponse
    {
        $role = app(CreateRoleAction::class)->execute(CreateRoleData::fromValidated($this->validated()), $this->actor());

        return (new RoleResource($role))->response()->setStatusCode(201);
    }
}
