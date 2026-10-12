<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class StoreSsoConnectionRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    public function rules(): array
    {
        return CreateSsoConnectionData::rules();
    }

    public function persist(): JsonResponse
    {
        $connection = app(CreateSsoConnectionAction::class)->execute($this->organization(), CreateSsoConnectionData::fromValidated($this->validated()));

        return (new SsoConnectionResource($connection))->response()->setStatusCode(201);
    }
}
