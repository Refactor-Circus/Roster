<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Roster\Http\Request;

final class IndexPermissionsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    public function rules(): array
    {
        return ListPermissionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return PermissionResource::collection(app(ListPermissionsAction::class)->execute(ListPermissionsData::fromValidated($this->validated())))->response();
    }
}
