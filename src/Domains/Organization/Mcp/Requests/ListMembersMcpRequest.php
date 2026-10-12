<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Organization\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Actions\ListMembersAction;
use RefactorCircus\Roster\Domains\Organization\Data\ListMembersData;
use RefactorCircus\Roster\Domains\Organization\Resources\MemberResource;

final class ListMembersMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.members.view';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListMembersData::rules($this->organization());
    }

    protected function handle(array $validated): ResponseFactory
    {
        $members = app(ListMembersAction::class)->execute($this->organization(), ListMembersData::fromValidated($validated));

        return MemberResource::collection($members)->toStructuredResponse();
    }
}
