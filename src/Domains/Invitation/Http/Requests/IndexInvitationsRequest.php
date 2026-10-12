<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use RefactorCircus\Roster\Domains\Invitation\Data\ListInvitationsData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Http\Requests\OrganizationRequest;

final class IndexInvitationsRequest extends OrganizationRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.view';
    }

    public function rules(): array
    {
        return ListInvitationsData::rules();
    }

    public function persist(): JsonResponse
    {
        return InvitationResource::collection(app(ListInvitationsAction::class)->execute($this->organization(), ListInvitationsData::fromValidated($this->validated())))->response();
    }
}
