<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Scim\Actions\ListScimTokensAction;
use RefactorCircus\Roster\Domains\Scim\Data\ListScimTokensData;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;

final class ListScimTokensMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListScimTokensData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $tokens = app(ListScimTokensAction::class)->execute($this->organization(), ListScimTokensData::fromValidated($validated));

        return ScimTokenResource::collection($tokens)->toStructuredResponse();
    }
}
