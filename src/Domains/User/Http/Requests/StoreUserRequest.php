<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\CreateUserAction;
use RefactorCircus\Roster\Domains\User\Data\CreateUserData;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;
use RefactorCircus\Roster\Http\Request;

final class StoreUserRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.users.create';
    }

    public function rules(): array
    {
        return CreateUserData::rules();
    }

    public function persist(): JsonResponse
    {
        $user = app(CreateUserAction::class)->execute(CreateUserData::fromValidated($this->validated()));

        return (new UserResource($user))->response()->setStatusCode(201);
    }
}
