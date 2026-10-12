<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\ApproveUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class ApproveUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    public function persist(): JsonResponse
    {
        $user = app(ApproveUserAction::class)->execute($this->targetUser(), $this->actor());

        return (new UserResource($user))->response();
    }
}
