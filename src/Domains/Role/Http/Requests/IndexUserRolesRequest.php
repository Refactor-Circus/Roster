<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Roster\Domains\User\Http\Requests\UserRequest;

final class IndexUserRolesRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListRoleAssignmentsData::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleAssignmentResource::collection(app(ListRoleAssignmentsAction::class)->execute(ListRoleAssignmentsData::fromValidated(['user' => $this->targetUser()->getRouteKey()] + $this->validated())))->response();
    }
}
