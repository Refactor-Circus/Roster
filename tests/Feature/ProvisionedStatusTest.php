<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Roster\Domains\Sso\Actions\SsoLoginAction;
use RefactorCircus\Roster\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;
use Workbench\App\Models\User;

it('reads the status a saved organization stores', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $acme->update(['provisioned_status' => 'pending']);

    expect(app(Roster::class)->provisionedStatus($acme->refresh()))->toBe(UserStatus::Pending);
});

it('provisions just-in-time SSO accounts through it, so a bad stored value never fails the sign-in', function (): void {
    require_once __DIR__.'/Sso/helpers.php';
    Exceptions::fake();

    $connection = acmeWithSso();
    $connection->organization->update(['provisioned_status' => 'bogus']);

    [$user] = app(SsoLoginAction::class)->execute($connection, new IdentityClaims('sub-1', 'ada@acme.test', 'Ada Lovelace'));

    expect(app(Users::class)->status($user))->toBe(UserStatus::Active);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[bogus]'));
});

it('provisions SCIM accounts through it, so a bad stored value never fails provisioning', function (): void {
    require_once __DIR__.'/Scim/helpers.php';
    Exceptions::fake();

    [$acme, $token] = scimOrg();
    $acme->update(['provisioned_status' => 'bogus']);
    test()->scimToken = $token;

    scim('POST', '/Users', oktaUser('one@acme.test', 'e1'))->assertCreated();

    expect(app(Users::class)->status(User::query()->where('email', 'one@acme.test')->sole()))->toBe(UserStatus::Active);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[bogus]'));
});
