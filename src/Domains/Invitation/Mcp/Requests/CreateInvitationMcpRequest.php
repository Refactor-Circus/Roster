<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;

final class CreateInvitationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + CreateInvitationData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitation = app(CreateInvitationAction::class)->execute($this->organization(), CreateInvitationData::fromValidated($validated), $this->actor());

        return (new InvitationResource($invitation))->toStructuredResponse();
    }
}
