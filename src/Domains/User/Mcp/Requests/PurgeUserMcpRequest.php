<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;

/**
 * Works on deleted records too.
 */
final class PurgeUserMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.users.purge';
    }

    protected function handle(array $validated): Response
    {
        app(PurgeUserAction::class)->execute($this->trashedUser(), $this->actor());

        return Response::text('Deleted permanently.');
    }
}
