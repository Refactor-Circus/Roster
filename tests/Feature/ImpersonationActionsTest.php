<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Event;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Roster\Domains\Impersonation\Actions\EnterImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Actions\ListImpersonationsAction;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StopImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\EnterImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Data\ListImpersonationsData;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartedImpersonation;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Data\StopImpersonationData;
use RefactorCircus\Roster\Domains\Impersonation\Events\ImpersonationStoppedActionEvent;
use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\User\Actions\SuspendUserAction;

function startImpersonating($target, $actor, array $data = []): StartedImpersonation
{
    return app(StartImpersonationAction::class)->execute($target, StartImpersonationData::from(['reason' => 'Ticket 42'] + $data), $actor);
}

function tokenFrom(string $url): string
{
    return basename((string) parse_url($url, PHP_URL_PATH));
}

it('issues a one-time link and stores only its hash', function (): void {
    $admin = user();
    $ada = user();

    $started = startImpersonating($ada, $admin);

    expect($started->url)->toContain('/roster/impersonate/')->toContain('signature=')
        ->and($started->impersonation->token_hash)->toBe(hash('sha256', tokenFrom($started->url)))
        ->and($started->impersonation->reason)->toBe('Ticket 42')
        ->and($started->impersonation->started_at)->toBeNull();
});

it('refuses yourself, inactive users and missing reasons', function (): void {
    $admin = user();

    expect(fn () => startImpersonating($admin, $admin))->toThrow(ValidationException::class);

    $ada = app(SuspendUserAction::class)->execute(user());

    expect(fn () => startImpersonating($ada, $admin))->toThrow(ValidationException::class)
        ->and(validator(['reason' => ''], StartImpersonationData::rules())->fails())->toBeTrue();
});

it('refuses super-admins unless you are one', function (): void {
    $admin = user();
    $boss = user();
    grant($boss, 'super-admin');

    expect(fn () => startImpersonating($boss, $admin))->toThrow(ValidationException::class);

    grant($admin, 'super-admin');

    expect(startImpersonating($boss, $admin)->impersonation->exists)->toBeTrue();
});

it('refuses anyone with permissions you lack', function (): void {
    $actor = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.users.impersonate', 'roster.users.view'])->id, 'user_id' => $actor->getKey()]);
    $strong = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.users.delete'])->id, 'user_id' => $strong->getKey()]);
    $weak = user();
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.users.view'])->id, 'user_id' => $weak->getKey()]);

    expect(fn () => startImpersonating($strong, $actor))->toThrow(ValidationException::class)
        ->and(startImpersonating($weak, $actor)->impersonation->exists)->toBeTrue();
});

it('limits organization-scoped impersonation to members', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $admin = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $admin->getRouteKey()));
    grant($admin, 'admin', $acme);
    $outsider = user();

    expect(fn () => startImpersonating($outsider, $admin, ['organization' => 'acme']))->toThrow(ValidationException::class);

    $member = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $member->getRouteKey()));

    expect(startImpersonating($member, $admin, ['organization' => 'acme'])->impersonation->organization_id)->toBe($acme->id);
});

it('lets only the impersonator use the link, once, before it expires', function (): void {
    $admin = user();
    $ada = user();
    $started = startImpersonating($ada, $admin);
    $token = tokenFrom($started->url);

    expect(fn () => app(EnterImpersonationAction::class)->execute(new EnterImpersonationData(token: $token), user()))->toThrow(ValidationException::class);

    $impersonation = app(EnterImpersonationAction::class)->execute(new EnterImpersonationData(token: $token), $admin);

    expect($impersonation->isActive())->toBeTrue()
        ->and($impersonation->token_hash)->toBeNull()
        ->and($impersonation->expires_at?->diffInMinutes(now(), true))->toBeGreaterThan(29)
        ->and(fn () => app(EnterImpersonationAction::class)->execute(new EnterImpersonationData(token: $token), $admin))->toThrow(ValidationException::class);

    $late = startImpersonating(user(), $admin);
    $this->travel(6)->minutes();

    expect(fn () => app(EnterImpersonationAction::class)->execute(new EnterImpersonationData(token: tokenFrom($late->url)), $admin))->toThrow(ValidationException::class);
});

it('refuses a link once the user became inactive', function (): void {
    $admin = user();
    $ada = user();
    $started = startImpersonating($ada, $admin);
    app(SuspendUserAction::class)->execute($ada);

    app(EnterImpersonationAction::class)->execute(new EnterImpersonationData(token: tokenFrom($started->url)), $admin);
})->throws(ValidationException::class);

it('stops impersonations idempotently and lists them', function (): void {
    $admin = user();
    $ada = user();
    $started = startImpersonating($ada, $admin);

    $stopped = app(StopImpersonationAction::class)->execute($started->impersonation, new StopImpersonationData(why: ImpersonationModel::ENDED_FORCED));
    $again = app(StopImpersonationAction::class)->execute($stopped, new StopImpersonationData(why: ImpersonationModel::ENDED_STOPPED));

    expect($again->end_reason)->toBe('forced')
        ->and(app(ListImpersonationsAction::class)->execute(new ListImpersonationsData(user: $ada->getRouteKey()))->total())->toBe(1)
        ->and(app(ListImpersonationsAction::class)->execute(new ListImpersonationsData(active: true))->total())->toBe(0);
});

it('ends expired impersonations and unused links on the sweep, on the record', function (): void {
    Event::fake([ImpersonationStoppedActionEvent::class]);
    $ids = fn (): array => ['impersonator_id' => user()->getKey(), 'user_id' => user()->getKey()];

    $expired = ImpersonationModel::factory()->started()->create($ids());
    $current = ImpersonationModel::factory()->started()->create($ids());
    $unusedLink = ImpersonationModel::factory()->create($ids());
    $freshLink = ImpersonationModel::factory()->create($ids());

    $expired->update(['expires_at' => now()->subMinute()]);
    $unusedLink->update(['link_expires_at' => now()->subMinute()]);

    $this->artisan('roster:end-expired-impersonations')->expectsOutputToContain('Ended 2 expired impersonations')->assertSuccessful();

    expect($expired->refresh()->end_reason)->toBe(ImpersonationModel::ENDED_EXPIRED)
        ->and($unusedLink->refresh()->end_reason)->toBe(ImpersonationModel::ENDED_EXPIRED)
        ->and($current->refresh()->ended_at)->toBeNull()
        ->and($freshLink->refresh()->ended_at)->toBeNull();

    Event::assertDispatchedTimes(ImpersonationStoppedActionEvent::class, 2);

    // Ended ones are left alone next time.
    $this->artisan('roster:end-expired-impersonations')->expectsOutputToContain('Ended 0 expired impersonations')->assertSuccessful();
});
