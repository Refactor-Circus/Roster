<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;

final class CreateScimTokenMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + CreateScimTokenData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $issued = app(CreateScimTokenAction::class)->execute($this->organization(), CreateScimTokenData::fromValidated($validated), $this->actor());

        return (new ScimTokenResource($issued->token))->additional(['token' => $issued->plain])->toStructuredResponse();
    }
}
