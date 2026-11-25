<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Authorization\Abilities;
use RefactorCircus\Roster\Support\Authorization\RosterAbilities;

/**
 * The permissions Roster's built-in roles held before roles moved to the role
 * store, with their descriptions: every one but `roster.roles.*`.
 *
 * @return array<string, string>
 */
function formerRosterPermissions(): array
{
    return [
        'roster.users.view' => 'View users.',
        'roster.users.create' => 'Create users.',
        'roster.users.update' => 'Edit users, their profiles and context.',
        'roster.users.delete' => 'Delete and restore users.',
        'roster.users.purge' => 'Permanently delete deleted users.',
        'roster.users.manage-status' => 'Suspend, deactivate and reactivate users.',
        'roster.users.approve' => 'Approve or reject accounts awaiting approval.',
        'roster.users.impersonate' => 'Act as another user, temporarily and on the record.',
        'roster.organizations.view' => 'View organizations.',
        'roster.organizations.create' => 'Create organizations.',
        'roster.organizations.update' => "Edit an organization's settings.",
        'roster.organizations.delete' => 'Delete and restore organizations.',
        'roster.organizations.purge' => 'Permanently delete deleted organizations.',
        'roster.organizations.transfer' => 'Transfer organization ownership.',
        'roster.organizations.sync' => 'Create and update organizations from external systems.',
        'roster.members.view' => 'View organization members.',
        'roster.members.manage' => 'Add and remove organization members.',
        'roster.teams.view' => 'View teams and their members.',
        'roster.teams.manage' => 'Create, edit and delete teams and seat members.',
        'roster.invitations.view' => 'View invitations.',
        'roster.invitations.manage' => 'Send and revoke invitations.',
        'roster.audit.view' => "Read Roster's history in the audit log.",
    ];
}

/**
 * The abilities Keystone's registry grants to a default role.
 *
 * @return array<int, string>
 */
function abilitiesGrantedTo(string $role): array
{
    return array_keys(array_filter(
        app(Abilities::class)->all(),
        fn (array $ability): bool => in_array($role, $ability['grantTo'], true),
    ));
}

it('defines every former Roster permission but the role ones in Keystone\'s abilities, with its description', function (): void {
    $roster = array_filter(
        app(Abilities::class)->all(),
        fn (string $ability): bool => str_starts_with($ability, 'roster.'),
        ARRAY_FILTER_USE_KEY,
    );

    expect(array_map(fn (array $ability): string => $ability['description'], $roster))
        ->toEqualCanonicalizing(formerRosterPermissions())
        ->and(array_keys($roster))->toEqualCanonicalizing(array_keys(formerRosterPermissions()))
        ->and(app(Abilities::class)->has('roster.roles.view'))->toBeFalse()
        ->and(app(Abilities::class)->has('roster.roles.manage'))->toBeFalse()
        ->and(app(Abilities::class)->has('roster.roles.assign'))->toBeFalse();
});

it('declares the same list it defines', function (): void {
    expect(RosterAbilities::ABILITIES)->toBe(formerRosterPermissions());
});

it('grants admin the organization-wide abilities, as the built-in admin role did', function (): void {
    expect(abilitiesGrantedTo('admin'))->toEqualCanonicalizing([
        'roster.organizations.view',
        'roster.organizations.update',
        'roster.organizations.delete',
        'roster.organizations.transfer',
        'roster.members.view',
        'roster.members.manage',
        'roster.teams.view',
        'roster.teams.manage',
        'roster.invitations.view',
        'roster.invitations.manage',
        'roster.audit.view',
    ]);
});

it('grants admin no user, create, sync or purge ability', function (): void {
    $admin = abilitiesGrantedTo('admin');

    expect(array_filter($admin, fn (string $ability): bool => str_starts_with($ability, 'roster.users.')))->toBe([])
        ->and($admin)->not->toContain('roster.organizations.create')
        ->and($admin)->not->toContain('roster.organizations.sync')
        ->and($admin)->not->toContain('roster.organizations.purge');
});

it('grants member what the built-in member role held', function (): void {
    expect(abilitiesGrantedTo('member'))->toEqualCanonicalizing([
        'roster.organizations.view',
        'roster.members.view',
        'roster.teams.view',
    ]);
});

it('grants no other default role, so the team lead is the role store\'s', function (): void {
    $roles = array_values(array_unique(array_merge(...array_values(array_map(
        fn (array $ability): array => $ability['grantTo'],
        array_filter(app(Abilities::class)->all(), fn (string $ability): bool => str_starts_with($ability, 'roster.'), ARRAY_FILTER_USE_KEY),
    )))));

    expect($roles)->toEqualCanonicalizing(['admin', 'member']);
});

it('blocks role management in any package while impersonating, without naming Roster\'s old role abilities', function (): void {
    $blocked = config('roster.impersonation.blocked');

    expect($blocked)->toBe([
        'roster.users.impersonate',
        'roster.users.delete',
        '*.roles.manage',
        '*.roles.assign',
    ])
        ->and(array_filter($blocked, fn (string $pattern): bool => str_starts_with($pattern, 'roster.roles.')))->toBe([]);
});
