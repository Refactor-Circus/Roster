<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Sso\Actions\ListSsoIdentitiesAction;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoIdentitiesData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoIdentityResource;
use RefactorCircus\Roster\Domains\User\Mcp\Requests\UserMcpRequest;

final class ListSsoIdentitiesMcpRequest extends UserMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function self(): Model
    {
        return $this->targetUser();
    }

    protected function rules(): array
    {
        return $this->userRules() + ListSsoIdentitiesData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $identities = app(ListSsoIdentitiesAction::class)->execute(ListSsoIdentitiesData::fromValidated(['user' => $this->targetUser()->getRouteKey()] + $validated));

        return SsoIdentityResource::collection($identities)->toStructuredResponse();
    }
}
