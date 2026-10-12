<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;

final class DeleteTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function handle(array $validated): Response
    {
        app(DeleteTeamAction::class)->execute($this->team());

        return Response::text('Team deleted.');
    }
}
