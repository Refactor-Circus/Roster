<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Schema;
use RefactorCircus\Roster\Support\Tables;

it('indexes the columns Roster looks up by', function (string $table, string $column): void {
    expect(Schema::hasIndex(Tables::name($table), [$column]))->toBeTrue();
})->with([
    ['organizations', 'name'],
    ['organization_domains', 'organization_id'],
    ['roles', 'organization_id'],
    ['profiles', 'current_organization_id'],
    ['profiles', 'current_team_id'],
    ['impersonations', 'organization_id'],
    ['impersonations', 'created_at'],
    ['transfers', 'organization_id'],
]);

it('indexes the organization-scoped lists in page order', function (string $table): void {
    expect(Schema::hasIndex(Tables::name($table), ['organization_id', 'created_at', 'id']))->toBeTrue();
})->with(['memberships', 'role_assignments', 'invitations']);
