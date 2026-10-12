<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Sso\Actions\ShowSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class ShowSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new SsoConnectionResource(app(ShowSsoConnectionAction::class)->execute($this->connection())))->toStructuredResponse();
    }
}
