<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Mcp\Requests;

use Laravel\Mcp\ResponseFactory;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Roster\Mcp\Request;

final class RevokeScimTokenMcpRequest extends Request
{
    private ?ScimTokenModel $resolved = null;

    protected function ability(): string
    {
        return 'roster.scim.manage';
    }

    protected function scope(): ?OrganizationModel
    {
        return $this->token()->organization;
    }

    protected function rules(): array
    {
        return ['token' => ['required', 'string']];
    }

    protected function handle(array $validated): ResponseFactory
    {
        return (new ScimTokenResource(app(RevokeScimTokenAction::class)->execute($this->token())))->toStructuredResponse();
    }

    private function token(): ScimTokenModel
    {
        return $this->resolved ??= ScimTokenModel::query()->whereKey($this->get('token'))->firstOrFail();
    }
}
