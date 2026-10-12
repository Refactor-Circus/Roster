<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\RestoreUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

/**
 * Works on deleted records too.
 */
final class RestoreUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(RestoreUserAction::class)->execute($this->trashedUser())))->toStructuredResponse();
    }
}
