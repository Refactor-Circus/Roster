<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Role\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Role\Actions\ShowRoleAction;
use RefactorCircus\Roster\Domains\Role\Resources\RoleResource;

final class ShowRoleRequest extends RoleRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    public function persist(): JsonResponse
    {
        return (new RoleResource(app(ShowRoleAction::class)->execute($this->role())))->response();
    }
}
