<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Permission\Services\Permissions;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;

it('grants super-admin by email', function (): void {
    $ada = user(['email' => 'ada@example.com']);

    $this->artisan('roster:grant-super-admin', ['email' => 'ADA@example.com'])->assertSuccessful();

    expect(app(Permissions::class)->isSuperAdmin($ada))->toBeTrue();

    // Idempotent.
    $this->artisan('roster:grant-super-admin', ['email' => 'ada@example.com'])->assertSuccessful();
});

it('fails for an unknown email', function (): void {
    $this->artisan('roster:grant-super-admin', ['email' => 'nobody@example.com'])->assertFailed();
});

it('restores missing built-ins without touching edits', function (): void {
    PermissionModel::query()->where('name', 'roster.audit.view')->delete();
    $member = RoleModel::query()->where('slug', 'member')->sole();
    $member->permissions()->detach();

    $this->artisan('roster:sync-permissions')->assertSuccessful();

    expect(PermissionModel::query()->where('name', 'roster.audit.view')->exists())->toBeTrue()
        // Admin edits to existing roles are kept.
        ->and($member->permissions()->count())->toBe(0);
});

it('no longer creates atrium.view', function (): void {
    expect(PermissionModel::query()->where('name', 'atrium.view')->exists())->toBeFalse();

    $this->artisan('roster:sync-permissions')->assertSuccessful();

    expect(PermissionModel::query()->where('name', 'atrium.view')->exists())->toBeFalse();
});

it('reports a stored atrium.view as unregistered and keeps it and its grants', function (): void {
    $permission = PermissionModel::query()->create(['name' => 'atrium.view', 'description' => 'Open the Atrium dashboard.', 'system' => true]);
    $role = roleWith(['atrium.view']);

    $this->artisan('roster:sync-permissions')
        ->expectsOutputToContain('System permission [atrium.view] is no longer registered')
        ->assertSuccessful();

    expect(PermissionModel::query()->whereKey($permission->getKey())->exists())->toBeTrue()
        ->and($role->permissions()->pluck('name')->all())->toBe(['atrium.view']);
});
