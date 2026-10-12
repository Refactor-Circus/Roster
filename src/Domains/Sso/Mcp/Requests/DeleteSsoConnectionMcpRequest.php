<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Sso\Actions\DeleteSsoConnectionAction;

final class DeleteSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function handle(array $validated): Response
    {
        app(DeleteSsoConnectionAction::class)->execute($this->connection());

        return Response::text('SSO connection deleted.');
    }
}
