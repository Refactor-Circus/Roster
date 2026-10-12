<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\ApproveUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class ApproveUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserResource(app(ApproveUserAction::class)->execute($this->targetUser(), $this->actor())))->toStructuredResponse();
    }
}
