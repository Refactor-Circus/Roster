<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\AcceptInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\AcceptInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;

final class AcceptInvitationRequest extends InvitationResponseRequest
{
    public function rules(): array
    {
        return AcceptInvitationData::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = app(AcceptInvitationAction::class)->execute(AcceptInvitationData::fromValidated($this->validated()), $this->respondent());

        return (new InvitationResource($invitation))->response();
    }
}
