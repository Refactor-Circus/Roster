<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;

final class StoreMemberRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    public function rules(): array
    {
        return AddMemberData::rules();
    }

    public function persist(): JsonResponse
    {
        $membership = app(AddMemberAction::class)->execute($this->organization(), AddMemberData::fromValidated($this->validated()));

        return (new MemberResource($membership))->response()->setStatusCode(201);
    }
}
