<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Mcp\Requests\OrganizationMcpRequest;
use RefactorCircus\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class CreateSsoConnectionMcpRequest extends OrganizationMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function rules(): array
    {
        return $this->organizationRules() + CreateSsoConnectionData::rules();
    }

    protected function handle(array $validated): ResponseFactory
    {
        $connection = app(CreateSsoConnectionAction::class)->execute($this->organization(), CreateSsoConnectionData::fromValidated($validated));

        return (new SsoConnectionResource($connection))->toStructuredResponse();
    }
}
