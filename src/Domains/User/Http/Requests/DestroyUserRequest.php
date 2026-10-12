<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\User\Actions\DeleteUserAction;

final class DestroyUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.delete';
    }

    public function persist(): Response
    {
        app(DeleteUserAction::class)->execute($this->targetUser(), $this->actor());

        return response()->noContent();
    }
}
