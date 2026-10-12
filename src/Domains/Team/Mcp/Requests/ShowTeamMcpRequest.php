<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Team\Actions\ShowTeamAction;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class ShowTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TeamResource(app(ShowTeamAction::class)->execute($this->team())))->toStructuredResponse();
    }
}
