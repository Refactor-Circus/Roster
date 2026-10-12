<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Mcp\Request;

final class CreateUserMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.create';
    }

    protected function rules(): array
    {
        return CreateUserData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(CreateUserAction::class)->execute(CreateUserData::fromValidated($validated))))->toStructuredResponse();
    }
}
