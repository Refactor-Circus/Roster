<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;
use RefactorCircus\Roster\Domains\User\Data\SuspendUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class SuspendUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    public function rules(): array
    {
        return SuspendUserData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(SuspendUserAction::class)->execute($this->targetUser(), SuspendUserData::fromValidated($this->validated()), $this->actor());

        return (new UserResource($user))->response();
    }
}
