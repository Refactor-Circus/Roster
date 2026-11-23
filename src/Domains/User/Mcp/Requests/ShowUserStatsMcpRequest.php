<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\User\Actions\ShowUserStatsAction;
use RefactorCircus\Roster\Domains\User\Http\Resources\UserStatsResource;
use RefactorCircus\Roster\Mcp\Request;

final class ShowUserStatsMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new UserStatsResource(app(ShowUserStatsAction::class)->execute()))->toStructuredResponse();
    }
}
