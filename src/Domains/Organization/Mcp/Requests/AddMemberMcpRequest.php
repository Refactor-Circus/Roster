<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;

final class AddMemberMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.members.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + AddMemberData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $membership = app(AddMemberAction::class)->execute($this->organization(), AddMemberData::fromValidated($validated));

        return (new MemberResource($membership))->toStructuredResponse();
    }
}
