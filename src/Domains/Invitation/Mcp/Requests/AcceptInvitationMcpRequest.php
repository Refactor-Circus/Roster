<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\AcceptInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\AcceptInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;

final class AcceptInvitationMcpRequest extends InvitationResponseMcpRequest
{
    protected function rules(): array
    {
        return AcceptInvitationData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new InvitationResource(app(AcceptInvitationAction::class)->execute(AcceptInvitationData::fromValidated($validated), $this->respondent())))->toStructuredResponse();
    }
}
