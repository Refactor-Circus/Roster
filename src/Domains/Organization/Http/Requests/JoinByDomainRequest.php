<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\JoinOrganizationsByDomainAction;
use RefactorCircus\Roster\Domains\Organization\Resources\OrganizationResource;
use RefactorCircus\Roster\Domains\User\Http\Requests\UserRequest;

final class JoinByDomainRequest extends UserRequest
{
    protected function ability(): string
    {
        return 'roster.users.update';
    }

    public function persist(): JsonResponse
    {
        return OrganizationResource::collection(app(JoinOrganizationsByDomainAction::class)->execute($this->targetUser()))->response();
    }
}
