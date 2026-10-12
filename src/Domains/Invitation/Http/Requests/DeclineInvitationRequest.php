<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Invitation\Actions\DeclineInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\DeclineInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;

final class DeclineInvitationRequest extends InvitationResponseRequest
{
    public function rules(): array
    {
        return DeclineInvitationData::rules();
    }

    public function persist(): JsonResponse
    {
        $invitation = app(DeclineInvitationAction::class)->execute(DeclineInvitationData::fromValidated($this->validated()), $this->respondent());

        return (new InvitationResource($invitation))->response();
    }
}
