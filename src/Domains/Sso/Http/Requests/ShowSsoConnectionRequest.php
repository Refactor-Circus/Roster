<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Sso\Actions\ShowSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class ShowSsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    public function persist(): JsonResponse
    {
        return (new SsoConnectionResource(app(ShowSsoConnectionAction::class)->execute($this->connection())))->response();
    }
}
