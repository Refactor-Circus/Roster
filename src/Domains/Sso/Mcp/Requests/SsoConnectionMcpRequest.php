<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Mcp\Request;

abstract class SsoConnectionMcpRequest extends Request
{
    private ?SsoConnectionModel $resolved = null;

    protected function connection(): SsoConnectionModel
    {
        return $this->resolved ??= SsoConnectionModel::query()->where('slug', $this->get('connection'))->firstOrFail();
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->connection()->organization;
    }

    /**
     * @return array<string, array<int, mixed>>
     */
    protected function connectionRules(): array
    {
        return ['connection' => ['required', 'string']];
    }

    /**
     * Every connection tool names its connection by slug.
     */
    protected function rules(): array
    {
        return $this->connectionRules();
    }
}
