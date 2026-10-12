<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class StoreTeamMemberRequest extends TeamRequest
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
        return AddTeamMemberData::rules();
    }

    public function persist(): JsonResponse
    {
        $team = app(AddTeamMemberAction::class)->execute($this->team(), AddTeamMemberData::fromValidated($this->validated()));

        return (new TeamResource($team))->response();
    }
}
