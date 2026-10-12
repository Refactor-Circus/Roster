<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Actions\RemoveMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Actions\RemoveTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;

function permissions(): Permissions
{
    $permissions = app(Permissions::class);
    $permissions->flush();

    return $permissions;
}

it('seeds the built-in permissions and roles', function (): void {
    expect(permissions()->knows('roster.users.view'))->toBeTrue()
        ->and(permissions()->knows('atrium.view'))->toBeTrue()
        ->and(RoleModel::query()->where('system', true)->pluck('slug')->sort()->values()->all())
        ->toBe(['admin', 'lead', 'member', 'super-admin']);
});

it('unions global, organization and team roles by scope', function (): void {
    $acme = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada->getRouteKey()));
    grant($ada, 'lead', team: $ops);
    RoleAssignmentModel::query()->create(['role_id' => roleWith(['roster.users.view'])->id, 'user_id' => $ada->getKey()]);

    $perms = permissions();

    expect($perms->for($ada))->toBe(['roster.users.view'])
        // The default member role, plus global.
        ->and($perms->for($ada, $acme))->toContain('roster.members.view', 'roster.users.view')
        ->and($perms->allows($ada, 'roster.teams.manage', $acme))->toBeFalse()
        // The team check includes the organization and global roles.
        ->and($perms->allows($ada, 'roster.teams.manage', $ops))->toBeTrue()
        ->and($perms->allows($ada, 'roster.members.view', $ops))->toBeTrue()
        ->and($perms->allows($ada, 'roster.users.view', $ops))->toBeTrue();
});

it('gives the owner every organization permission but no global-only ones', function (): void {
    $owner = user();
    $acme = organization($owner);

    expect(permissions()->allows($owner, 'roster.members.manage', $acme))->toBeTrue()
        ->and(permissions()->allows($owner, 'roster.roles.assign', $acme))->toBeTrue()
        ->and(permissions()->allows($owner, 'roster.users.delete', $acme))->toBeFalse()
        ->and(permissions()->allows($owner, 'roster.members.manage', organization()))->toBeFalse();
});

it('lets a super-admin do everything', function (): void {
    $ada = user();
    grant($ada, 'super-admin');

    expect(permissions()->isSuperAdmin($ada))->toBeTrue()
        ->and(permissions()->for($ada))->toBe(permissions()->all());
});

it('honours configured super-admin emails only once verified', function (): void {
    config()->set('roster.super_admins', ['ADA@example.com']);

    $unverified = user(['email' => 'ada@example.com', 'email_verified_at' => null]);

    expect(permissions()->isSuperAdmin($unverified))->toBeFalse();

    $unverified->markEmailAsVerified();

    expect(permissions()->isSuperAdmin($unverified->refresh()))->toBeTrue();
});

it('revokes scoped roles when a member leaves', function (): void {
    $acme = organization();
    $ada = user();
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada->getRouteKey()));
    grant($ada, 'lead', team: $ops);

    app(RemoveTeamMemberAction::class)->execute($ops, $ada);

    expect(RoleAssignmentModel::query()->whereNotNull('team_id')->count())->toBe(0)
        ->and(RoleAssignmentModel::query()->where('user_id', $ada->getKey())->count())->toBe(1);

    app(RemoveMemberAction::class)->execute($acme, $ada);

    expect(RoleAssignmentModel::query()->where('user_id', $ada->getKey())->count())->toBe(0);
});

it('assigns no default role when disabled', function (): void {
    config()->set('roster.roles.default_member', null);
    $acme = organization();
    $ada = user();

    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));

    expect(RoleAssignmentModel::query()->where('user_id', $ada->getKey())->count())->toBe(0);
});
