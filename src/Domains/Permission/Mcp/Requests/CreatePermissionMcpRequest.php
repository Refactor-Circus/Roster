<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Permission\Resources\PermissionResource;
use RefactorCircus\Roster\Mcp\Request;

final class CreatePermissionMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.roles.manage';
    }

    protected function rules(): array
    {
        return CreatePermissionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new PermissionResource(app(CreatePermissionAction::class)->execute(CreatePermissionData::fromValidated($validated))))->toStructuredResponse();
    }
}
