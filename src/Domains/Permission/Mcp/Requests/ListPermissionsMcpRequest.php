<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Permission\Actions\ListPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Data\ListPermissionsData;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Roster\Mcp\Request;

final class ListPermissionsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function rules(): array
    {
        return ListPermissionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $permissions = app(ListPermissionsAction::class)->execute(ListPermissionsData::fromValidated($validated));

        return PermissionResource::collection($permissions)->toStructuredResponse();
    }
}
