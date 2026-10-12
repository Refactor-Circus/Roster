<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Http\Requests;

use Illuminate\Http\JsonResponse;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Scim\Resources\ScimTokenResource;
use RefactorCircus\Roster\Http\Request;

final class RevokeScimTokenRequest extends Request
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

    public function persist(): JsonResponse
    {
        return (new ScimTokenResource(app(RevokeScimTokenAction::class)->execute($this->token())))->response();
    }

    private function token(): ScimTokenModel
    {
        return $this->resolved ??= ScimTokenModel::query()->whereKey($this->route('token'))->firstOrFail();
    }
}
