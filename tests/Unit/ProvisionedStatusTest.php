<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Roster\Domains\Organization\Models\OrganizationModel;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Roster;
use RefactorCircus\Roster\Support\ProvisionedStatus;
use RefactorCircus\Roster\Tests\Fixtures\Statuses\MemberStatus;

function provisioning(?string $status): OrganizationModel
{
    return (new OrganizationModel)->forceFill(['name' => 'Acme', 'slug' => 'acme', 'provisioned_status' => $status]);
}

it('starts provisioned users with the organization\'s status', function (string $stored, UserStatus $status): void {
    Exceptions::fake();

    expect((new ProvisionedStatus(UserStatus::class))->for(provisioning($stored)))->toBe($status);

    Exceptions::assertNothingReported();
})->with([
    'pending' => ['pending', UserStatus::Pending],
    'active' => ['active', UserStatus::Active],
]);

it('starts them active, without reporting, when the organization has no status stored', function (): void {
    Exceptions::fake();

    expect((new ProvisionedStatus(UserStatus::class))->for(provisioning(null)))->toBe(UserStatus::Active);

    Exceptions::assertNothingReported();
});

it('resolves against the statuses it is given', function (): void {
    Exceptions::fake();
    $statuses = new ProvisionedStatus(MemberStatus::class);

    expect($statuses->for(provisioning('applied')))->toBe(MemberStatus::pending())
        ->and($statuses->for(provisioning(null)))->toBe(MemberStatus::active());

    Exceptions::assertNothingReported();
});

it('falls back to active and reports an invalid stored status, naming the organization and the value', function (): void {
    Exceptions::fake();

    expect((new ProvisionedStatus(UserStatus::class))->for(provisioning('bogus')))->toBe(UserStatus::Active);

    Exceptions::assertReportedCount(1);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[acme]')
        && str_contains($exception->getMessage(), '[bogus]'));
});

it('resolves through the app\'s own user statuses from Roster::provisionedStatus()', function (): void {
    config(['roster.statuses.users' => MemberStatus::class]);
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning('applied')))->toBe(MemberStatus::Applied)
        ->and(app(Roster::class)->provisionedStatus(provisioning('applied')))->toBe(MemberStatus::pending())
        ->and(app(Roster::class)->provisionedStatus(provisioning(null)))->toBe(MemberStatus::active());

    Exceptions::assertNothingReported();
});

it('falls back to the remapped active status when the stored value is not one of the app\'s statuses', function (): void {
    config(['roster.statuses.users' => MemberStatus::class]);
    Exceptions::fake();

    expect(app(Roster::class)->provisionedStatus(provisioning('pending')))->toBe(MemberStatus::Member);

    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[pending]'));
});
