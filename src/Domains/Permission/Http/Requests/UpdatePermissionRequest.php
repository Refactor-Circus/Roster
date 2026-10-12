<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Permission\Actions\UpdatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\UpdatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;

final class UpdatePermissionRequest extends PermissionRequest
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function rules(): array
    {
        return UpdatePermissionData::rules();
    }

    public function persist(): JsonResponse
    {
        return (new PermissionResource(app(UpdatePermissionAction::class)->execute($this->permission(), UpdatePermissionData::fromValidated($this->validated()))))->response();
    }
}
