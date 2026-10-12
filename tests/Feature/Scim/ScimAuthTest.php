<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use RefactorCircus\Roster\Domains\Scim\Actions\CreateScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Actions\RevokeScimTokenAction;
use RefactorCircus\Roster\Domains\Scim\Data\CreateScimTokenData;
use RefactorCircus\Roster\Domains\Scim\Models\ScimTokenModel;

beforeEach(function (): void {
    [, $this->scimToken] = scimOrg();
});

it('accepts a valid token and stamps its use', function (): void {
    scim('GET', '/Users')->assertOk()->assertHeader('Content-Type', 'application/scim+json');

    expect(ScimTokenModel::query()->sole()->last_used_at)->not->toBeNull();
});

it('refuses missing, wrong, revoked, expired and other-organization tokens', function (string $case): void {
    $token = match ($case) {
        'missing' => '',
        'wrong' => 'scim_nope',
        'revoked' => (function (): string {
            app(RevokeScimTokenAction::class)->execute(ScimTokenModel::query()->sole());

            return $this->scimToken;
        })(),
        'expired' => (function (): string {
            ScimTokenModel::query()->update(['expires_at' => now()->subDay()]);

            return $this->scimToken;
        })(),
        'other organization' => app(CreateScimTokenAction::class)->execute(organization(attributes: ['name' => 'Globex']), new CreateScimTokenData(name: 'x'))->plain,
    };

    scim('GET', '/Users', token: $token)
        ->assertUnauthorized()
        ->assertJsonPath('schemas.0', 'urn:ietf:params:scim:api:messages:2.0:Error')
        ->assertJsonPath('status', '401');
})->with(['missing', 'wrong', 'revoked', 'expired', 'other organization']);
