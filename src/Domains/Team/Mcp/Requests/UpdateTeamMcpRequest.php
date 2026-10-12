<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class UpdateTeamMcpRequest extends TeamMcpRequest
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
        return $this->teamRules() + UpdateTeamData::rules($this->team());
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TeamResource(app(UpdateTeamAction::class)->execute($this->team(), UpdateTeamData::fromValidated($validated))))->toStructuredResponse();
    }
}
