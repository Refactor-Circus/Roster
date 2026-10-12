<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Role\Actions\ShowRoleAction;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;

final class ShowRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new RoleResource(app(ShowRoleAction::class)->execute($this->role())))->toStructuredResponse();
    }
}
