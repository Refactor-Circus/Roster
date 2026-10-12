<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Permission\Actions\DeletePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Mcp\Request;

final class DeletePermissionMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return ['name' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        $permission = PermissionModel::query()->where('name', $validated['name'])->firstOrFail();

        app(DeletePermissionAction::class)->execute($permission);

        return Response::text('Permission deleted.');
    }
}
