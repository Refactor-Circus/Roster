<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;

final class StoreInvitationRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    public function rules(): array
    {
        return CreateInvitationData::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = app(CreateInvitationAction::class)->execute($this->organization(), CreateInvitationData::fromValidated($this->validated()), $this->actor());

        return (new InvitationResource($invitation))->response()->setStatusCode(201);
    }
}
