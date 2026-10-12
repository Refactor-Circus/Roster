<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\Response;
use RefactorCircus\Roster\Domains\Sso\Actions\DeleteSsoConnectionAction;

final class DestroySsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    public function persist(): Response
    {
        app(DeleteSsoConnectionAction::class)->execute($this->connection());

        return response()->noContent();
    }
}
