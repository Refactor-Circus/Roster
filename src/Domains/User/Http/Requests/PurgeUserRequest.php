<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\User\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\User\Actions\PurgeUserAction;

/**
 * Works on deleted users too.
 */
final class PurgeUserRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.purge';
    }

    public function persist(): Response
    {
        app(PurgeUserAction::class)->execute($this->trashedUser(), $this->actor());

        return response()->noContent();
    }
}
