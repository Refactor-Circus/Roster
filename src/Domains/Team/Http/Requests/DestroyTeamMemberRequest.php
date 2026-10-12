<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Team\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Models\TeamModel;
use RefactorCircus\Roster\Support\Users;

final class DestroyTeamMemberRequest extends TeamRequest
{
    protected function ability(): string
    {
        return 'roster.teams.manage';
    }

    protected function scope(): TeamModel
    {
        return $this->team();
    }

    public function persist(): Response
    {
        app(RemoveTeamMemberAction::class)->execute($this->team(), app(Users::class)->findOrFail($this->route('user')));

        return response()->noContent();
    }
}
