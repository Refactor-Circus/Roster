<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Authorization\GatePermissions;
use RefactorCircus\Keystone\Authorization\NullRoles;
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\StartImportData;
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;
use RefactorCircus\Roster\Domains\User\Services\Approvals;
use RefactorCircus\Roster\Support\Authorization\ImpersonationAwarePermissions;
use Workbench\App\Models\User;

/**
 * Roster with authorization on and no role store: only Keystone's defaults
 * (`GatePermissions`, `NullRoles`) are bound, so the host's Gate decides.
 */
beforeEach(function (): void {
    config()->set('roster.authorization', true);
});

it('runs on Keystone\'s defaults with no role store bound', function (): void {
    $permissions = app(Permissions::class);

    expect($permissions)->toBeInstanceOf(ImpersonationAwarePermissions::class)
        ->and((new ReflectionProperty($permissions, 'permissions'))->getValue($permissions))->toBeInstanceOf(GatePermissions::class)
        ->and(app(Roles::class))->toBeInstanceOf(NullRoles::class);
});

it('boots, migrates and answers its API', function (): void {
    $owner = user();
    $acme = organization($owner, ['name' => 'Acme']);

    $this->actingAs($owner)->getJson(route('roster.organizations.show', $acme))
        ->assertOk()
        ->assertJsonPath('data.name', 'Acme');
});

it('lets an organization\'s owner update it through the owner rule', function (): void {
    $owner = user();
    $acme = organization($owner, ['name' => 'Acme']);

    $this->actingAs($owner)->patchJson(route('roster.organizations.update', $acme), ['name' => 'Acme Inc'])
        ->assertOk();

    expect($acme->refresh()->name)->toBe('Acme Inc');

    $this->actingAs(user())->patchJson(route('roster.organizations.update', $acme), ['name' => 'Taken'])
        ->assertForbidden();
});

it('lets a user the host grants roster.users.view with Gate::define() list users', function (): void {
    $viewer = user(['email' => 'viewer@example.com']);
    Gate::define('roster.users.view', fn (User $user): bool => $user->email === 'viewer@example.com');

    $this->actingAs($viewer)->getJson(route('roster.users.index'))->assertOk();
});

it('refuses a user with neither ownership nor a Gate grant', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $nobody = user();

    $this->actingAs($nobody)->getJson(route('roster.users.index'))->assertForbidden();
    $this->actingAs($nobody)->patchJson(route('roster.organizations.update', $acme), ['name' => 'Nope'])->assertForbidden();
});

it('assigns no role when someone joins, without failing', function (): void {
    $acme = organization(attributes: ['name' => 'Acme']);
    $ada = user();

    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));

    expect($acme->membershipFor($ada))->not->toBeNull()
        ->and(app(Roles::class)->of($ada, $acme))->toBe([]);
});

it('notifies nobody about a user awaiting approval, without failing', function (): void {
    config()->set('roster.users.approvals.notify_approvers', true);
    Notification::fake();

    app(Approvals::class)->pending(user());

    expect(app(Approvals::class)->approvers())->toHaveCount(0);
    Notification::assertNothingSent();
});

it('makes an import row with a role an unknown-role error', function (): void {
    $owner = user();
    organization($owner, ['name' => 'Acme', 'slug' => 'acme']);

    $transfer = app(StartImportAction::class)->execute(new StartImportData(
        type: TransferType::ImportMembers,
        organization: 'acme',
        content: "email,name,teams,role\nada@example.com,Ada,,member",
    ), $owner);

    expect($transfer->rows()[0]['action'])->toBe('error')
        ->and($transfer->rows()[0]['reasons'])->toBe([__('roster::roster.import_unknown_role', ['role' => 'member'])]);
});

it('lets only a host-defined super-admin impersonate', function (): void {
    $ada = user();
    $actor = user(['email' => 'actor@example.com']);
    $start = fn () => app(StartImpersonationAction::class)
        ->execute($ada, new StartImpersonationData(reason: 'Ticket 1'), $actor);

    expect($start)->toThrow(ValidationException::class, __('roster::roster.impersonate_escalation'));

    Gate::define(Permissions::SUPER_ADMIN, fn (User $user): bool => $user->email === 'actor@example.com');

    expect($start()->impersonation->user_id)->toBe($ada->getKey());
});
