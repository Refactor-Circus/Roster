<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class StoreTeamRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    public function rules(): array
    {
        return CreateTeamData::rules($this->organization());
    }

    public function persist(): JsonResponse
    {
        $team = app(CreateTeamAction::class)->execute($this->organization(), CreateTeamData::fromValidated($this->validated()));

        return (new TeamResource($team))->response()->setStatusCode(201);
    }
}
