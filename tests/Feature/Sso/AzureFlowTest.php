<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Psr7\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;

beforeEach(function (): void {
    $this->connection = acmeWithSso(protocol: 'azure');
});

function graphReturns(array $me): void
{
    app()->instance('roster.sso.http', new Client(['handler' => HandlerStack::create(new MockHandler([
        new Response(200, [], (string) json_encode(['access_token' => 'at', 'token_type' => 'Bearer'])),
        new Response(200, [], (string) json_encode($me)),
    ]))]));
}

it('signs in through Microsoft Entra ID using the tenant-specific authority', function (): void {
    $location = (string) $this->get(route('roster.sso.start', 'acme-sso'))->headers->get('Location');

    expect($location)->toStartWith('https://login.microsoftonline.com/11111111-2222-3333-4444-555555555555/oauth2/v2.0/authorize?');

    graphReturns(['id' => 'oid-1', 'displayName' => 'Ada', 'userPrincipalName' => 'Ada@acme.test', 'mail' => 'ada@acme.test']);

    $this->get(route('roster.sso.callback', ['acme-sso', 'code' => 'c', 'state' => queryOf($location)['state']]))->assertRedirect('/');

    expect(Auth::user()?->getAttribute('email'))->toBe('ada@acme.test');
});

it('trusts the userPrincipalName, never the mail attribute', function (): void {
    // A tenant admin can set `mail` to anyone's address; the UPN must be on a
    // domain the tenant verified.
    user(['email' => 'ceo@acme.test']);
    $location = (string) $this->get(route('roster.sso.start', 'acme-sso'))->headers->get('Location');

    graphReturns(['id' => 'oid-evil', 'displayName' => 'Eve', 'userPrincipalName' => 'eve@evil.test', 'mail' => 'ceo@acme.test']);

    $this->get(route('roster.sso.callback', ['acme-sso', 'code' => 'c', 'state' => queryOf($location)['state']]))
        ->assertSessionHasErrors('sso');

    expect(Auth::check())->toBeFalse();
});

it('refuses multi-tenant authorities', function (string $tenant): void {
    app(CreateSsoConnectionAction::class)->execute($this->connection->organization, CreateSsoConnectionData::from([
        'name' => 'Entra', 'protocol' => 'azure', 'tenant' => $tenant, 'client_id' => 'x', 'client_secret' => 'y',
    ]));
})->with(['common', 'organizations', 'consumers'])->throws(ValidationException::class);
