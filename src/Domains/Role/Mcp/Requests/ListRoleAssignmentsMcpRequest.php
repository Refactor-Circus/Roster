<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;

final class ListRoleAssignmentsMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return $this->userRules() + ListRoleAssignmentsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $assignments = app(ListRoleAssignmentsAction::class)->execute(ListRoleAssignmentsData::fromValidated(['user' => $this->targetUser()->getRouteKey()] + $validated));

        return RoleAssignmentResource::collection($assignments)->toStructuredResponse();
    }
}
