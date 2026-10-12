<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Support\Users;

final class RemoveMemberMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ['user' => ['required']];
    }

    protected function handle(array $validated): Response
    {
        app(RemoveMemberAction::class)->execute($this->organization(), app(Users::class)->findOrFail($validated['user']));

        return Response::text('Member removed.');
    }
}
