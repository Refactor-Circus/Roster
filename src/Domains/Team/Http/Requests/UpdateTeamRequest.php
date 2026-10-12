<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Team\Actions\UpdateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\UpdateTeamData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class UpdateTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }

    public function rules(): array
    {
        return UpdateTeamData::rules($this->team());
    }

    public function persist(): JsonResponse
    {
        $team = app(UpdateTeamAction::class)->execute($this->team(), UpdateTeamData::fromValidated($this->validated()));

        return (new TeamResource($team))->response();
    }
}
