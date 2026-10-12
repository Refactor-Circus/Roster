<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Roster\Http\Request;

final class StorePermissionRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    public function rules(): array
    {
        return CreatePermissionData::rules();
    }

    public function persist(): JsonResponse
    {
        return (new PermissionResource(app(CreatePermissionAction::class)->execute(CreatePermissionData::fromValidated($this->validated()))))->response()->setStatusCode(201);
    }
}
