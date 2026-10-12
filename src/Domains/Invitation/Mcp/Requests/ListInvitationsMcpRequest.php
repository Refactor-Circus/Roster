<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\ListInvitationsAction;
use RefactorCircus\Roster\Domains\Invitation\Data\ListInvitationsData;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;

final class ListInvitationsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.view';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListInvitationsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitations = app(ListInvitationsAction::class)->execute($this->organization(), ListInvitationsData::fromValidated($validated));

        return InvitationResource::collection($invitations)->toStructuredResponse();
    }
}
