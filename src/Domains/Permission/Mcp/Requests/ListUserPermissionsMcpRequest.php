<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Permission\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Permission\Actions\ListUserPermissionsAction;
use RefactorCircus\Roster\Domains\Permission\Data\ListUserPermissionsData;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;

final class ListUserPermissionsMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.roles.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return $this->userRules() + ListUserPermissionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return Response::structured(['data' => app(ListUserPermissionsAction::class)->execute($this->targetUser(), ListUserPermissionsData::fromValidated($validated))]);
    }
}
