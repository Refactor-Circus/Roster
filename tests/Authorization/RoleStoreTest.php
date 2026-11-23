<?php

declare(strict_types=1);

use RefactorCircus\Keystone\Authorization\Contracts\Permissions;
use RefactorCircus\Keystone\Authorization\Contracts\Roles;
use RefactorCircus\Keystone\Testing\FakePermissions;
use RefactorCircus\Keystone\Testing\FakeRoles;
use RefactorCircus\Roster\Support\Authorization\RosterAbilities;

it('runs on Keystone\'s fakes as the role store', function (): void {
    expect(app(Permissions::class))->toBeInstanceOf(FakePermissions::class)
        ->and(app(Roles::class))->toBeInstanceOf(FakeRoles::class);
});

it('defines super-admin, admin and member from RosterAbilities', function (): void {
    $acme = organization();
    $globalOnly = ['roster.users.view', 'roster.organizations.create', 'roster.organizations.sync', 'roster.organizations.purge'];

    $super = user();
    grant($super, 'super-admin');
    $admin = user();
    grant($admin, 'admin', $acme);
    $member = user();
    grant($member, 'member', $acme);

    $granted = fn (string $role): array => array_keys(array_filter(
        RosterAbilities::ABILITIES,
        fn (string $ability): bool => in_array($role, RosterAbilities::grantTo($ability), true),
        ARRAY_FILTER_USE_KEY,
    ));

    expect(app(Permissions::class)->isSuperAdmin($super))->toBeTrue()
        ->and(app(Roles::class)->of($admin, $acme))->toBe(['admin'])
        ->and(app(Permissions::class)->abilities($admin, $acme))->toEqualCanonicalizing($granted('admin'))
        ->and(app(Permissions::class)->abilities($member, $acme))->toBe(['roster.members.view', 'roster.organizations.view', 'roster.teams.view'])
        ->and(array_intersect($globalOnly, app(Permissions::class)->abilities($admin, $acme)))->toBe([])
        // admin and member are held in an organization, never globally.
        ->and(app(Roles::class)->exists('admin'))->toBeFalse()
        ->and(app(Roles::class)->exists('member'))->toBeFalse()
        ->and(app(Roles::class)->exists('admin', $acme))->toBeTrue();
});

it('grants roles through FakeRoles, held in their scope', function (): void {
    $acme = organization();
    $ada = user();

    grant($ada, 'admin', $acme);

    expect(app(Roles::class)->of($ada, $acme))->toBe(['admin'])
        ->and(app(Permissions::class)->allows($ada, 'roster.teams.manage', $acme))->toBeTrue()
        ->and(app(Permissions::class)->allows($ada, 'roster.teams.manage', organization(attributes: ['name' => 'Globex'])))->toBeFalse();
});
