<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Sso\Actions\UpdateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class UpdateSsoConnectionRequest extends SsoConnectionRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    public function rules(): array
    {
        return UpdateSsoConnectionData::rules($this->connection());
    }

    public function persist(): JsonResponse
    {
        return (new SsoConnectionResource(app(UpdateSsoConnectionAction::class)->execute($this->connection(), UpdateSsoConnectionData::fromValidated($this->validated()))))->response();
    }
}
