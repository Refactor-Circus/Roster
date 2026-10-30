<?php

declare(strict_types=1);

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Support\Facades\Validator;
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Facades\Roster;
use RefactorCircus\Roster\Support\Rules\NoExternalSignIn;

/**
 * A registered sign-in requirement claiming every email of one domain, the
 * way an identity package claims an organization's verified domain.
 */
final class DomainSignInRequirement implements SignInRequirement
{
    public function for(string $email): ?ExternalSignIn
    {
        return str_ends_with($email, '@acme.test')
            ? new ExternalSignIn(key: 'acme-idp', label: 'Acme Corp', url: 'https://idp.acme.test/start')
            : null;
    }
}

/**
 * A registered sign-in requirement whose identity provider is down.
 */
final class UnreachableSignInRequirement implements SignInRequirement
{
    public function for(string $email): ?ExternalSignIn
    {
        throw new RuntimeException('identity provider unreachable');
    }
}

/**
 * @return array<string, list<string>>
 */
function noExternalSignInErrors(mixed $email): array
{
    return Validator::make(['email' => $email], ['email' => ['nullable', new NoExternalSignIn]])->errors()->toArray();
}

it('fails with the translated message, naming the organization and the url', function (): void {
    Roster::requireSignIn(new DomainSignInRequirement);

    expect(noExternalSignInErrors('ada@acme.test'))
        ->toBe(['email' => ['Acme Corp requires single sign-on. Sign in at https://idp.acme.test/start']])
        ->and(__('roster::roster.external_sign_in_required', ['organization' => 'Acme Corp', 'url' => 'https://idp.acme.test/start']))
        ->toBe('Acme Corp requires single sign-on. Sign in at https://idp.acme.test/start');
});

it('passes when the requirement answers null for the email', function (): void {
    Roster::requireSignIn(new DomainSignInRequirement);

    expect(noExternalSignInErrors('grace@elsewhere.test'))->toBe([]);
});

it('passes when no requirement is registered', function (): void {
    expect(noExternalSignInErrors('ada@acme.test'))->toBe([]);
});

it('passes a super-admin the requirement would claim', function (): void {
    grant(user(['email' => 'boss@acme.test']), 'super-admin');
    Roster::requireSignIn(new DomainSignInRequirement);

    expect(noExternalSignInErrors('boss@acme.test'))->toBe([]);
});

it('lets a throwing requirement propagate, so the login fails closed', function (): void {
    Roster::requireSignIn(new UnreachableSignInRequirement);

    noExternalSignInErrors('ada@acme.test');
})->throws(RuntimeException::class, 'identity provider unreachable');

it('keeps the generic rule in Support, not in a domain', function (): void {
    expect(class_exists('RefactorCircus\Roster\Domains\Sso\Rules\NotSsoEnforced'))->toBeFalse()
        ->and(new NoExternalSignIn)->toBeInstanceOf(ValidationRule::class)
        ->and(__('roster::roster.sso_required'))->toBe('roster::roster.sso_required');
});
