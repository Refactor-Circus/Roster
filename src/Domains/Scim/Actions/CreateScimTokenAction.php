<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Domains\Scim\Actions;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Roster\Domains\Scim\Data\IssuedScimToken;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokenCreatedActionEvent;
use RefactorCircus\Roster\Domains\Scim\Events\ScimTokenCreatingActionEvent;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;

final class CreateScimTokenAction
{
    /**
     * Issue a token an identity provider uses to provision the organization.
     * With `sso_connection`, provisioned users' externalIds become that
     * connection's SSO subjects.
     */
    public function execute(OrganizationModel $organization, CreateScimTokenData $data, ?Model $actor = null): IssuedScimToken
    {
        $connection = null;

        if ($data->ssoConnection !== null && $data->ssoConnection !== '') {
            $connection = SsoConnectionModel::query()
                ->where('organization_id', $organization->getKey())
                ->where('slug', $data->ssoConnection)
                ->first() ?? throw ValidationException::withMessages(['sso_connection' => __('roster::roster.unknown_sso_connection')]);
        }

        ScimTokenCreatingActionEvent::dispatch($organization, $data);

        $plain = 'scim_'.Str::random(48);

        $token = DB::transaction(fn (): ScimTokenModel => ScimTokenModel::query()->create([
            'organization_id' => $organization->getKey(),
            'name' => $data->name,
            'token_hash' => hash('sha256', $plain),
            'sso_connection_id' => $connection?->getKey(),
            'created_by' => $actor?->getKey(),
            'expires_at' => $data->expiresInDays !== null ? now()->addDays($data->expiresInDays) : null,
        ]));

        $token->load(['organization', 'ssoConnection']);

        ScimTokenCreatedActionEvent::dispatch($token);

        return new IssuedScimToken($token, $plain);
    }
}
