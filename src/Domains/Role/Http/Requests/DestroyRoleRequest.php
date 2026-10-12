<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Role\Actions\DeleteRoleAction;

final class DestroyRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function persist(): Response
    {
        app(DeleteRoleAction::class)->execute($this->role());

        return response()->noContent();
    }
}
