<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\AssignRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\AssignRoleData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;
use RefactorCircus\Roster\Support\Scopes;

final class AssignRoleMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return Scopes::fromInput($this->get('organization'), $this->get('team'));
    }

    protected function rules(): array
    {
        return $this->userRules() + AssignRoleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $assignment = app(AssignRoleAction::class)->execute($this->targetUser(), AssignRoleData::fromValidated($validated), $this->actor());

        return (new RoleAssignmentResource($assignment))->toStructuredResponse();
    }
}
