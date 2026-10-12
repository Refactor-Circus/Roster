<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Data\ListMembersData;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;

final class IndexMembersRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    public function rules(): array
    {
        return ListMembersData::rules($this->organization());
    }

    public function persist(): JsonResponse
    {
        return MemberResource::collection(app(ListMembersAction::class)->execute($this->organization(), ListMembersData::fromValidated($this->validated())))->response();
    }
}
