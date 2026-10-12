<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\User\Actions\RestoreUserAction;
use RefactorCircus\Roster\Domains\User\Resources\UserResource;

/**
 * Works on deleted users too.
 */
final class RestoreUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    public function persist(): JsonResponse
    {
        return (new UserResource(app(RestoreUserAction::class)->execute($this->trashedUser())))->response();
    }
}
