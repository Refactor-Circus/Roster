<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\ReactivateUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class ReactivateUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    public function persist(): JsonResponse
    {
        $user = app(ReactivateUserAction::class)->execute($this->targetUser(), $this->actor());

        return (new UserResource($user))->response();
    }
}
