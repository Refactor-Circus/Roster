<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\Sso\Actions\SsoLoginAction;
use RefactorCircus\Roster\Domains\Sso\Data\IdentityClaims;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\Users;
use RefactorCircus\Roster\Tests\Fixtures\Statuses\MemberStatus;
use Workbench\App\Models\User;

function provisioning(?string $status): OrganizationModel
{
    return (new OrganizationModel)->forceFill(['name' => 'Acme', 'slug' => 'acme', 'provisioned_status' => $status]);
}

it('starts provisioned users with the organization\'s status', function (string $stored, UserStatus $status): void {
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning($stored)))->toBe($status);

    Exceptions::assertNothingReported();
})->with([
    'pending' => ['pending', UserStatus::Pending],
    'active' => ['active', UserStatus::Active],
]);

it('starts them active, without reporting, when the organization has no status stored', function (): void {
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning(null)))->toBe(UserStatus::Active);

    Exceptions::assertNothingReported();
});

it('resolves the status through the app\'s own user statuses', function (): void {
    config(['roster.statuses.users' => MemberStatus::class]);
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning('applied')))->toBe(MemberStatus::Applied)
        ->and(app(Roster::class)->provisionedStatus(provisioning('applied')))->toBe(MemberStatus::pending())
        ->and(app(Roster::class)->provisionedStatus(provisioning(null)))->toBe(MemberStatus::active());

    Exceptions::assertNothingReported();
});

it('falls back to active and reports an invalid stored status, naming the organization and the value', function (): void {
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning('bogus')))->toBe(UserStatus::Active);

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[acme]')
        && str_contains($exception->getMessage(), '[bogus]'));
});

it('falls back to the remapped active status when the stored value is not one of the app\'s statuses', function (): void {
    config(['roster.statuses.users' => MemberStatus::class]);
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning('pending')))->toBe(MemberStatus::Member);

    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[pending]'));
});

it('reads the status a saved organization stores', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $acme->update(['provisioned_status' => 'pending']);

    expect(app(Roster::class)->provisionedStatus($acme->refresh()))->toBe(UserStatus::Pending);
});

it('provisions just-in-time SSO accounts through it, so a bad stored value never fails the sign-in', function (): void {
    require_once dirname(__DIR__).'/Feature/Sso/helpers.php';
    Exceptions::fake();

    $connection = acmeWithSso();
    $connection->organization->update(['provisioned_status' => 'bogus']);

    [$user] = app(SsoLoginAction::class)->execute($connection, new IdentityClaims('sub-1', 'ada@acme.test', 'Ada Lovelace'));

    expect(app(Users::class)->status($user))->toBe(UserStatus::Active);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[bogus]'));
});

it('provisions SCIM accounts through it, so a bad stored value never fails provisioning', function (): void {
    require_once dirname(__DIR__).'/Feature/Scim/helpers.php';
    Exceptions::fake();

    [$acme, $token] = scimOrg();
    $acme->update(['provisioned_status' => 'bogus']);
    test()->scimToken = $token;

    scim('POST', '/Users', oktaUser('one@acme.test', 'e1'))->assertCreated();

    expect(app(Users::class)->status(User::query()->where('email', 'one@acme.test')->sole()))->toBe(UserStatus::Active);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[bogus]'));
});
