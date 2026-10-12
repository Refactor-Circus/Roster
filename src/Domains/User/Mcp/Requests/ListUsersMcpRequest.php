<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\ListUsersAction;
use RefactorCircus\Roster\Domains\User\Data\ListUsersData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Request;

final class ListUsersMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    protected function rules(): array
    {
        return ListUsersData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $users = app(ListUsersAction::class)->execute(ListUsersData::fromValidated($validated));

        return UserResource::collection($users)->toStructuredResponse();
    }
}
