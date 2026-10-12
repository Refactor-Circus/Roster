<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\ReactivateUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class ReactivateUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(ReactivateUserAction::class)->execute($this->targetUser(), $this->actor())))->toStructuredResponse();
    }
}
