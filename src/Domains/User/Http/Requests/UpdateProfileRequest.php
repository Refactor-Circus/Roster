<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\UpdateProfileAction;
use RefactorCircus\Roster\Domains\User\Data\UpdateProfileData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

final class UpdateProfileRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return UpdateProfileData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(UpdateProfileAction::class)->execute($this->targetUser(), UpdateProfileData::fromValidated($this->validated()));

        return (new UserResource($user))->response();
    }
}
