<?php

declare(strict_types=1);

use Illuminate\Testing\TestResponse;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Data\CreateScimTokenData;

/**
 * Acme (owning acme.test) with a SCIM token; returns [organization, token].
 *
 * @return array{0: OrganizationModel, 1: string}
 */
function scimOrg(array $token = []): array
{
    $acme = organization(attributes: ['name' => 'Acme', 'domains' => ['acme.test']]);
    $issued = app(CreateScimTokenAction::class)->execute($acme, CreateScimTokenData::from(['name' => 'Okta'] + $token));

    return [$acme, $issued->plain];
}

function scim(string $method, string $path, array $body = [], ?string $token = null, array $headers = []): TestResponse
{
    $token ??= test()->scimToken;

    return test()->json($method, '/scim/v2/acme'.$path, $body, ['Authorization' => 'Bearer '.$token, 'Content-Type' => 'application/scim+json'] + $headers);
}

/**
 * The user Okta sends when assigning someone to the app.
 *
 * @return array<string, mixed>
 */
function oktaUser(string $email = 'ada@acme.test', string $externalId = '00u1okta'): array
{
    return [
        'schemas' => ['urn:ietf:params:scim:schemas:core:2.0:User'],
        'userName' => $email,
        'name' => ['givenName' => 'Ada', 'familyName' => 'Lovelace'],
        'emails' => [['primary' => true, 'value' => $email, 'type' => 'work']],
        'displayName' => 'Ada Lovelace',
        'externalId' => $externalId,
        'active' => true,
    ];
}

/**
 * @return array<string, mixed>
 */
function patchOps(array $operations): array
{
    return ['schemas' => ['urn:ietf:params:scim:api:messages:2.0:PatchOp'], 'Operations' => $operations];
}
