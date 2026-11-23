<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\ShowUserStatsAction;
use RefactorCircus\Roster\Domains\User\Http\Resources\UserStatsResource;
use RefactorCircus\Roster\Http\Request;

final class ShowUserStatsRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.view';
    }

    public function persist(): JsonResponse
    {
        return (new UserStatsResource(app(ShowUserStatsAction::class)->execute()))->response();
    }
}
