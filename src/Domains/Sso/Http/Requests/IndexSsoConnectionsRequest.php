<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;
use RefactorCircus\Roster\Domains\Sso\Actions\ListSsoConnectionsAction;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class IndexSsoConnectionsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    public function rules(): array
    {
        return ListSsoConnectionsData::rules();
    }

    public function persist(): JsonResponse
    {
        return SsoConnectionResource::collection(app(ListSsoConnectionsAction::class)->execute(ListSsoConnectionsData::fromValidated(['organization' => $this->organization()->slug] + $this->validated())))->response();
    }
}
