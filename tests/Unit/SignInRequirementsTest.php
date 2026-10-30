<?php

declare(strict_types=1);

use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Roster;

/**
 * Answers every email with its sign-in (or nothing), counting the questions.
 */
final class CountingSignInRequirement implements SignInRequirement
{
    public int $asked = 0;

    public function __construct(public ?ExternalSignIn $answer = null) {}

    public function for(string $email): ?ExternalSignIn
    {
        $this->asked++;

        return $this->answer;
    }
}

/**
 * A requirement whose identity provider is down.
 */
final class FailingSignInRequirement implements SignInRequirement
{
    public int $asked = 0;

    public function for(string $email): ?ExternalSignIn
    {
        $this->asked++;

        throw new RuntimeException('identity provider unreachable');
    }
}

function externalSignIn(string $key): ExternalSignIn
{
    return new ExternalSignIn(key: $key, label: ucfirst($key), url: 'https://'.$key.'.test/start');
}

it('holds the sign-in as a value, with no record by default', function (): void {
    $signIn = externalSignIn('okta');

    expect($signIn->key)->toBe('okta')
        ->and($signIn->label)->toBe('Okta')
        ->and($signIn->url)->toBe('https://okta.test/start')
        ->and($signIn->record)->toBeNull()
        ->and((new ExternalSignIn('okta', 'Okta', 'https://okta.test/start', user()))->record)->not->toBeNull();
});

it('answers null when no requirement claims the email', function (): void {
    app(Roster::class)->requireSignIn(new CountingSignInRequirement);

    expect(app(Roster::class)->externalSignInFor('ada@acme.test'))->toBeNull();
});

it('asks the requirements in registration order, and the first answer wins', function (): void {
    $silent = new CountingSignInRequirement;
    $first = new CountingSignInRequirement(externalSignIn('first'));
    $second = new CountingSignInRequirement(externalSignIn('second'));

    app(Roster::class)->requireSignIn($silent);
    app(Roster::class)->requireSignIn($first);
    app(Roster::class)->requireSignIn($second);

    expect(app(Roster::class)->externalSignInFor('ada@acme.test')?->key)->toBe('first')
        ->and($silent->asked)->toBe(1)
        ->and($first->asked)->toBe(1)
        ->and($second->asked)->toBe(0);
});

it('lets a throwing requirement propagate: sign-in fails closed', function (): void {
    app(Roster::class)->requireSignIn(new FailingSignInRequirement);

    expect(fn () => app(Roster::class)->externalSignInFor('ada@acme.test'))
        ->toThrow(RuntimeException::class, 'identity provider unreachable');
});

it('gives super-admins null before any requirement is asked', function (): void {
    $boss = user(['email' => 'boss@acme.test']);
    grant($boss, 'super-admin');

    $failing = new FailingSignInRequirement;
    app(Roster::class)->requireSignIn($failing);

    expect(app(Roster::class)->externalSignInFor('boss@acme.test'))->toBeNull()
        ->and($failing->asked)->toBe(0);
});

it('gives super-admins null even when a requirement claims their email', function (): void {
    $boss = user(['email' => 'boss@acme.test']);
    grant($boss, 'super-admin');
    user(['email' => 'ada@acme.test']);

    app(Roster::class)->requireSignIn(new CountingSignInRequirement(externalSignIn('okta')));

    expect(app(Roster::class)->externalSignInFor('boss@acme.test'))->toBeNull()
        ->and(app(Roster::class)->externalSignInFor('ada@acme.test')?->key)->toBe('okta')
        ->and(app(Roster::class)->externalSignInFor('nobody@acme.test')?->key)->toBe('okta');
});

it('resolves a class-string requirement from the container lazily, once', function (): void {
    $made = 0;
    $requirement = new CountingSignInRequirement(externalSignIn('okta'));
    app()->bind(CountingSignInRequirement::class, function () use (&$made, $requirement): CountingSignInRequirement {
        $made++;

        return $requirement;
    });

    app(Roster::class)->requireSignIn(CountingSignInRequirement::class);

    expect($made)->toBe(0);

    expect(app(Roster::class)->externalSignInFor('ada@acme.test')?->key)->toBe('okta')
        ->and(app(Roster::class)->externalSignInFor('grace@acme.test')?->key)->toBe('okta')
        ->and($made)->toBe(1)
        ->and($requirement->asked)->toBe(2);
});

it('asks a class or an instance registered twice only once', function (): void {
    $requirement = new CountingSignInRequirement;
    app()->instance(CountingSignInRequirement::class, $requirement);

    app(Roster::class)->requireSignIn(CountingSignInRequirement::class);
    app(Roster::class)->requireSignIn(CountingSignInRequirement::class);

    $instance = new CountingSignInRequirement;
    app(Roster::class)->requireSignIn($instance);
    app(Roster::class)->requireSignIn($instance);

    app(Roster::class)->externalSignInFor('ada@acme.test');

    expect($requirement->asked)->toBe(1)
        ->and($instance->asked)->toBe(1);
});

it('keeps registrations for the life of the process, across requests', function (): void {
    app(Roster::class)->requireSignIn(new CountingSignInRequirement(externalSignIn('okta')));

    // Roster itself is per request (Octane, queue workers); the requirements
    // registered from boot() are not, or enforcement would vanish after one.
    app()->forgetScopedInstances();

    expect(app(Roster::class)->externalSignInFor('ada@acme.test')?->key)->toBe('okta');
});

it('refuses a class that is not a sign-in requirement when it is registered, naming it', function (string $class): void {
    app(Roster::class)->requireSignIn($class);
})->with([
    'not a requirement' => [stdClass::class],
    'no such class' => ['Acme\\MissingRequirement'],
])->throws(InvalidArgumentException::class, 'is not a '.SignInRequirement::class);

it('no longer offers ssoRequiredFor()', function (): void {
    expect(method_exists(Roster::class, 'ssoRequiredFor'))->toBeFalse();
});
