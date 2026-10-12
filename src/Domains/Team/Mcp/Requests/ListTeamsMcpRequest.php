<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Team\Actions\ListTeamsAction;
use RefactorCircus\Roster\Domains\Team\Data\ListTeamsData;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class ListTeamsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListTeamsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $teams = app(ListTeamsAction::class)->execute($this->organization(), ListTeamsData::fromValidated($validated));

        return TeamResource::collection($teams)->toStructuredResponse();
    }
}
