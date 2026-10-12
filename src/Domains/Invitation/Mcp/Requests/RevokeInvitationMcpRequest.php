<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Invitation\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Invitation\Actions\RevokeInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Resources\InvitationResource;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;

final class RevokeInvitationMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.invitations.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ['invitation' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        $invitation = $this->organization()->invitations()->whereKey($validated['invitation'])->firstOrFail();

        return (new InvitationResource(app(RevokeInvitationAction::class)->execute($invitation)))->toStructuredResponse();
    }
}
