<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class CreateTeamMcpRequest extends TeamMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + CreateTeamData::rules($this->organization());
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new TeamResource(app(CreateTeamAction::class)->execute($this->organization(), CreateTeamData::fromValidated($validated))))->toStructuredResponse();
    }
}
