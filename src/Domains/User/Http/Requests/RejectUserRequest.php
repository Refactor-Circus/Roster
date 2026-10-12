<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\RejectUserAction;
use RefactorCircus\Roster\Domains\User\Data\RejectUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class RejectUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.approve';
    }

    public function rules(): array
    {
        return RejectUserData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(RejectUserAction::class)->execute($this->targetUser(), RejectUserData::fromValidated($this->validated()), $this->actor());

        return (new UserResource($user))->response();
    }
}
