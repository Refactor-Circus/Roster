<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Sso\Mcp\Requests;

use Illuminate\Database\Eloquent\Model;
use Laravel\Mcp\Response;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Actions\UnlinkSsoIdentityAction;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Mcp\Request;

final class UnlinkSsoIdentityMcpRequest extends Request
{
    protected function ability(): string
    {
        return 'roster.sso.manage';
    }

    private ?SsoIdentityModel $resolved = null;

    protected function scope(): ?OrganizationModel
    {
        return $this->identity()->connection?->organization;
    }

    protected function self(): ?Model
    {
        $actor = $this->actor();

        return $this->identity()->ownedBy($actor) ? $actor : null;
    }

    private function identity(): SsoIdentityModel
    {
        return $this->resolved ??= SsoIdentityModel::query()->whereKey($this->get('identity'))->firstOrFail();
    }

    protected function rules(): array
    {
        return ['identity' => ['required', 'string']];
    }

    protected function handle(array $validated): Response
    {
        app(UnlinkSsoIdentityAction::class)->execute($this->identity());

        return Response::text('Identity unlinked.');
    }
}
