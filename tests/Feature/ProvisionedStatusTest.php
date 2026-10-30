<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Exceptions;
use RefactorCircus\Roster\Domains\User\Enums\UserStatus;
use RefactorCircus\Roster\Roster;

it('reads the status a saved organization stores', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $acme->update(['provisioned_status' => 'pending']);

    expect(app(Roster::class)->provisionedStatus($acme->refresh()))->toBe(UserStatus::Pending);
});

it('starts externally provisioned accounts active on a bad stored value, and reports it', function (): void {
    Exceptions::fake();

    $acme = organization(attributes: ['name' => 'Acme']);
    $acme->update(['provisioned_status' => 'bogus']);

    expect(app(Roster::class)->provisionedStatus($acme->refresh()))->toBe(UserStatus::Active);
    Exceptions::assertReported(fn (InvalidArgumentException $exception): bool => str_contains($exception->getMessage(), '[bogus]'));
});
