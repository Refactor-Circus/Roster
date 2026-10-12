<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Http\Requests;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Sso\Actions\ListSsoIdentitiesAction;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoIdentityResource;
use RefactorCircus\Roster\Domains\User\Http\Requests\UserRequest;

final class IndexUserSsoIdentitiesRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    public function rules(): array
    {
        return ListSsoIdentitiesData::rules();
    }

    public function persist(): JsonResponse
    {
        return SsoIdentityResource::collection(app(ListSsoIdentitiesAction::class)->execute(ListSsoIdentitiesData::fromValidated(['user' => $this->targetUser()->getRouteKey()] + $this->validated())))->response();
    }
}
