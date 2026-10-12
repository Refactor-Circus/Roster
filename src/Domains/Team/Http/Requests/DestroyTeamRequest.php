<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Team\Actions\DeleteTeamAction;

final class DestroyTeamRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    public function persist(): Response
    {
        app(DeleteTeamAction::class)->execute($this->team());

        return response()->noContent();
    }
}
