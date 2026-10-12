<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Team\Actions\ShowTeamAction;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Domains\Team\Resources\TeamResource;

final class ShowTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.view';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }

    public function persist(): JsonResponse
    {
        return (new TeamResource(app(ShowTeamAction::class)->execute($this->team())))->response();
    }
}
