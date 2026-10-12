<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Role\Actions\UpdateRoleAction;
use RefactorCircus\Roster\Domains\Role\Data\UpdateRoleData;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;

final class UpdateRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return $this->roleRules() + UpdateRoleData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleResource(app(UpdateRoleAction::class)->execute($this->role(), UpdateRoleData::fromValidated($validated), $this->actor())))->toStructuredResponse();
    }
}
