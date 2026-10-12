<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Sso\Actions\UpdateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;

final class UpdateSsoConnectionMcpRequest extends SsoConnectionMcpRequest
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    protected function rules(): array
    {
        return $this->connectionRules() + UpdateSsoConnectionData::rules($this->connection());
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new SsoConnectionResource(app(UpdateSsoConnectionAction::class)->execute($this->connection(), UpdateSsoConnectionData::fromValidated($validated))))->toStructuredResponse();
    }
}
