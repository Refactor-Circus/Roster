<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class AddTeamMemberMcpRequest extends TeamMcpRequest
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
        return $this->teamRules() + AddTeamMemberData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TeamResource(app(AddTeamMemberAction::class)->execute($this->team(), AddTeamMemberData::fromValidated($validated))))->toStructuredResponse();
    }
}
