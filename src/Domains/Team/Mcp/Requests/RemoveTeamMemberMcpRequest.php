<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;
use RefactorCircus\Roster\Support\Users;

final class RemoveTeamMemberMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }

    protected function rules(): array
    {
        return $this->teamRules() + ['user' => ['required']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TeamResource(app(RemoveTeamMemberAction::class)->execute($this->team(), app(Users::class)->findOrFail($validated['user']))))->toStructuredResponse();
    }
}
