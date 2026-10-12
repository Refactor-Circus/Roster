<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;

final class DeleteUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    protected function handle(array $validated): Response
    {
        app(DeleteUserAction::class)->execute($this->targetUser(), $this->actor());

        return Response::text('User deleted.');
    }
}
