<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Sso\Actions\ListSsoConnectionsAction;
use RefactorCircus\Roster\Domains\Sso\Data\ListSsoConnectionsData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class ListSsoConnectionsMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.view';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + ListSsoConnectionsData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $connections = app(ListSsoConnectionsAction::class)->execute(ListSsoConnectionsData::fromValidated($validated));

        return SsoConnectionResource::collection($connections)->toStructuredResponse();
    }
}
