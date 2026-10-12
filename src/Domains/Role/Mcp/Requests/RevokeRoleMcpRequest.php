<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Role\Actions\RevokeRoleAction;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;

final class RevokeRoleMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.assign';
    }

    protected function scope(): OrganizationModel|TeamModel|null
    {
        return $this->assignment()->team ?? $this->assignment()->organization;
    }

    private function assignment(): RoleAssignmentModel
    {
        return RoleAssignmentModel::query()
            ->where('user_id', $this->targetUser()->getKey())
            ->whereKey($this->get('assignment'))
            ->firstOrFail();
    }

    protected function rules(): array
    {
        return $this->userRules() + ['assignment' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(RevokeRoleAction::class)->execute($this->assignment(), $this->actor());

        return Response::text('Role revoked.');
    }
}
