<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Http\Requests\UserRequest;
use RefactorCircus\Roster\Support\Scopes;

final class AssignRoleRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->input('organization'), $this->input('team'));
    }

    public function rules(): array
    {
        return AssignRoleData::rules();
    }

    public function persist(): JsonResponse
    {
        $assignment = app(AssignRoleAction::class)->execute($this->targetUser(), AssignRoleData::fromValidated($this->validated()), $this->actor());

        return (new RoleAssignmentResource($assignment))->response()->setStatusCode(201);
    }
}
