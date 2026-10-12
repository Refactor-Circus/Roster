<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\DeactivateUserAction;
use RefactorCircus\Roster\Domains\User\Data\DeactivateUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class DeactivateUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.manage-status';
    }

    public function rules(): array
    {
        return DeactivateUserData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(DeactivateUserAction::class)->execute($this->targetUser(), DeactivateUserData::fromValidated($this->validated()), $this->actor());

        return (new UserResource($user))->response();
    }
}
