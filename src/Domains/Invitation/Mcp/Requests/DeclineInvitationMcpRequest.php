<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\DeclineInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\DeclineInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;

final class DeclineInvitationMcpRequest extends InvitationResponseMcpRequest
{
    protected function rules(): array
    {
        return DeclineInvitationData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new InvitationResource(app(DeclineInvitationAction::class)->execute(DeclineInvitationData::fromValidated($validated), $this->respondent())))->toStructuredResponse();
    }
}
