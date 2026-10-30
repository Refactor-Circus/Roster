<?php

declare(strict_types=1);

require_once __DIR__.'/helpers.php';

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Two\User as SocialiteUser;
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Domains\Sso\Actions\CreateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Actions\UpdateSsoConnectionAction;
use RefactorCircus\Roster\Domains\Sso\Data\CreateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Data\UpdateSsoConnectionData;
use RefactorCircus\Roster\Domains\Sso\Models\SsoConnectionModel;
use RefactorCircus\Roster\Domains\Sso\Models\SsoIdentityModel;
use RefactorCircus\Roster\Domains\Sso\Resources\SsoConnectionResource;
use RefactorCircus\Roster\Domains\Sso\Services\Sso;
use RefactorCircus\Roster\Domains\Sso\Services\SsoSignInRequirement;
use RefactorCircus\Roster\Domains\Sso\Support\NotSsoEnforced;
use RefactorCircus\Roster\Roster;

/**
 * Swap the identity provider for one that vouches for `$claims`.
 */
function idpVouchesFor(string $subject, string $email): void
{
    app()->instance(Sso::class, new class(request(), $subject, $email) extends Sso
    {
        public function __construct($request, private string $subject, private string $email)
        {
            parent::__construct($request);
        }

        public function provider(SsoConnectionModel $connection): Provider
        {
            $user = (new SocialiteUser)->setRaw([])->map(['id' => $this->subject, 'email' => $this->email, 'name' => 'Someone']);

            return new class($user) implements Provider
            {
                public function __construct(private SocialiteUser $user) {}

                public function redirect()
                {
                    return redirect('https://idp.test/authorize');
                }

                public function user()
                {
                    return $this->user;
                }
            };
        }
    });
}

it('maps SAML connections to the provider config and serves SP metadata', function (): void {
    $connection = acmeWithSso(protocol: 'saml');
    $config = app(Sso::class)->samlConfig($connection);

    expect($config['entityid'])->toBe('https://idp.example.com/saml')
        ->and($config['acs'])->toBe('https://idp.example.com/saml/sso')
        ->and($config['sp_acs'])->toBe('roster/sso/acme-sso/callback');

    $connection->update(['config' => ['metadata_url' => 'https://idp.example.com/metadata']]);

    expect(app(Sso::class)->samlConfig($connection->refresh()))->toHaveKey('metadata', 'https://idp.example.com/metadata')
        ->not->toHaveKey('acs');

    $this->get(route('roster.sso.metadata', 'acme-sso'))
        ->assertOk()
        ->assertSee('entityID="'.route('roster.sso.metadata', 'acme-sso').'"', false)
        ->assertSee(route('roster.sso.callback', 'acme-sso'), false);
});

it('accepts the SAML assertion post without a CSRF token', function (): void {
    acmeWithSso(protocol: 'saml');
    idpVouchesFor('name-id-1', 'ada@acme.test');

    $this->post(route('roster.sso.callback', 'acme-sso'), ['SAMLResponse' => 'stub'])->assertRedirect('/');

    expect(Auth::user()?->getAttribute('email'))->toBe('ada@acme.test');
});

it('links an identity to the signed-in account', function (): void {
    acmeWithSso();
    $ada = user(['email' => 'ada@gmail.test']);
    $this->actingAs($ada);
    idpVouchesFor('okta-ada', 'ada@acme.test');

    $this->post(route('roster.sso.link', 'acme-sso'))->assertRedirect('https://idp.test/authorize');
    $this->get(route('roster.sso.callback', 'acme-sso'))->assertRedirect('/')->assertSessionHas('status');

    expect(SsoIdentityModel::query()->sole()->user_id)->toBe($ada->getKey());

    // Someone else cannot claim the same identity.
    $this->actingAs(user());
    $this->post(route('roster.sso.link', 'acme-sso'));
    $this->get(route('roster.sso.callback', 'acme-sso'))->assertSessionHasErrors('sso');
});

it('enforces SSO for the organization\'s domains, except for super-admins', function (): void {
    $connection = acmeWithSso(['enforced' => true]);

    expect(app(Roster::class)->externalSignInFor('ada@acme.test')?->record?->is($connection))->toBeTrue()
        ->and(app(Roster::class)->externalSignInFor('ada@gmail.test'))->toBeNull()
        ->and(validator(['email' => 'ada@acme.test'], ['email' => [new NotSsoEnforced]])->errors()->first('email'))->toContain('requires single sign-on');

    $boss = user(['email' => 'boss@acme.test']);
    grant($boss, 'super-admin');

    expect(app(Roster::class)->externalSignInFor('boss@acme.test'))->toBeNull();
});

it('points the enforced sign-in at the connection\'s start page, in the same message', function (): void {
    acmeWithSso(['enforced' => true]);

    $signIn = app(Roster::class)->externalSignInFor('ada@acme.test');

    expect($signIn)->toBeInstanceOf(ExternalSignIn::class)
        ->and($signIn?->key)->toBe('acme-sso')
        ->and($signIn?->label)->toBe('Acme')
        ->and($signIn?->url)->toBe(route('roster.sso.start', 'acme-sso'))
        ->and(validator(['email' => 'ada@acme.test'], ['email' => [new NotSsoEnforced]])->errors()->first('email'))
        ->toBe(__('roster::roster.sso_required', ['organization' => 'Acme', 'url' => route('roster.sso.start', 'acme-sso')]));
});

it('ignores connections that are not enforced or not enabled, and values that are not emails', function (): void {
    $connection = acmeWithSso(['enforced' => false]);

    expect(app(Roster::class)->externalSignInFor('ada@acme.test'))->toBeNull();

    $connection->update(['enforced' => true, 'enabled' => false]);

    expect(app(Roster::class)->externalSignInFor('ada@acme.test'))->toBeNull()
        ->and(app(Roster::class)->externalSignInFor('acme.test'))->toBeNull()
        ->and(validator(['email' => ['ada@acme.test']], ['email' => [new NotSsoEnforced]])->passes())->toBeTrue();
});

it('registers SSO enforcement as a sign-in requirement', function (): void {
    acmeWithSso(['enforced' => true]);

    expect(app(SsoSignInRequirement::class)->for('ada@acme.test')?->key)->toBe('acme-sso')
        ->and(app(SsoSignInRequirement::class)->for('ada@gmail.test'))->toBeNull()
        ->and(method_exists(Roster::class, 'ssoRequiredFor'))->toBeFalse();
});

it('fails the password login closed when a sign-in requirement throws', function (): void {
    app(Roster::class)->requireSignIn(new class implements SignInRequirement
    {
        public function for(string $email): ?ExternalSignIn
        {
            throw new RuntimeException('identity provider unreachable');
        }
    });

    expect(fn () => validator(['email' => 'ada@acme.test'], ['email' => [new NotSsoEnforced]])->validate())
        ->toThrow(RuntimeException::class, 'identity provider unreachable');
});

it('keeps secrets encrypted, hidden and redacted', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);

    $connection = app(CreateSsoConnectionAction::class)->execute($acme, new CreateSsoConnectionData(
        name: 'Okta',
        protocol: 'oidc',
        issuer: 'https://idp.test',
        clientId: 'app',
        clientSecret: 'top-secret',
    ));

    $raw = (string) DB::table('roster_sso_connections')->value('config');

    expect($raw)->not->toContain('top-secret')
        ->and(json_encode((new SsoConnectionResource($connection))->resolve()))->not->toContain('top-secret');

    // A blank secret keeps the old one; a new one replaces it.
    app(UpdateSsoConnectionAction::class)->execute($connection, new UpdateSsoConnectionData(clientSecret: ''));
    expect($connection->refresh()->setting('client_secret'))->toBe('top-secret');

    app(UpdateSsoConnectionAction::class)->execute($connection, new UpdateSsoConnectionData(clientSecret: 'rotated'));
    expect($connection->refresh()->setting('client_secret'))->toBe('rotated');
});

it('validates the settings each protocol needs', function (array $data): void {
    app(CreateSsoConnectionAction::class)->execute(organization(), CreateSsoConnectionData::from(['name' => 'X'] + $data));
})->with([
    'oidc without secret' => [['protocol' => 'oidc', 'issuer' => 'https://idp.test', 'client_id' => 'a']],
    'azure without tenant' => [['protocol' => 'azure', 'client_id' => 'a', 'client_secret' => 'b']],
    'saml without anything' => [['protocol' => 'saml']],
])->throws(ValidationException::class);
