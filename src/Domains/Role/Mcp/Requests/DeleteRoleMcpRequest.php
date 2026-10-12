<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Role\Actions\DeleteRoleAction;

final class DeleteRoleMcpRequest extends RoleMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function handle(array $validated): Response
    {
        app(DeleteRoleAction::class)->execute($this->role());

        return Response::text('Role deleted.');
    }
}
