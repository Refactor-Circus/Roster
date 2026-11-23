<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Role\Actions\ListRoleAssignmentsAction;
use RefactorCircus\Roster\Domains\Role\Data\ListRoleAssignmentsData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleAssignmentResource;

/**
 * Every role assignment in the organization and its teams, checked in that
 * organization.
 */
final class IndexOrganizationRoleAssignmentsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    public function rules(): array
    {
        return ListRoleAssignmentsData::rules();
    }

    public function persist(): JsonResponse
    {
        return RoleAssignmentResource::collection(app(ListRoleAssignmentsAction::class)->execute(ListRoleAssignmentsData::fromValidated(['organization' => $this->organization()->slug] + $this->validated())))->response();
    }
}
