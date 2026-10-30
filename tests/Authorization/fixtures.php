<?php

declare(strict_types=1);

use RefactorCircus\Roster\Domains\Impersonation\Models\ImpersonationModel;
use RefactorCircus\Roster\Domains\Invitation\Models\InvitationModel;
use RefactorCircus\Roster\Domains\Organization\Actions\AddMemberAction;
use RefactorCircus\Roster\Domains\Organization\Data\AddMemberData;
use RefactorCircus\Roster\Domains\Permission\Models\PermissionModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleAssignmentModel;
use RefactorCircus\Roster\Domains\Role\Models\RoleModel;
use RefactorCircus\Roster\Domains\Team\Actions\AddTeamMemberAction;
use RefactorCircus\Roster\Domains\Team\Actions\CreateTeamAction;
use RefactorCircus\Roster\Domains\Team\Data\AddTeamMemberData;
use RefactorCircus\Roster\Domains\Team\Data\CreateTeamData;
use RefactorCircus\Roster\Domains\Transfer\Models\TransferModel;

/**
 * One of everything, for exercising every route and tool: an organization
 * `acme` with team `ops`, a member `target`, a pending invitation, an app
 * permission `invoices.edit`, and a custom role assigned to the target.
 *
 * @return array<string, mixed>
 */
function authorizationWorld(): array
{
    $acme = organization(attributes: ['name' => 'Acme']);
    $target = user(['email' => 'target@example.com']);
    app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $target->getRouteKey()));
    $ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
    app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $target->getRouteKey()));

    $invitation = InvitationModel::factory()->create(['organization_id' => $acme->id, 'email' => 'new@example.com']);
    PermissionModel::query()->create(['name' => 'invoices.edit']);
    $role = RoleModel::factory()->create(['scope' => 'global', 'slug' => 'custom']);
    $assignment = RoleAssignmentModel::query()->create(['role_id' => $role->id, 'user_id' => $target->getKey()]);

    $impersonation = ImpersonationModel::factory()->create(['impersonator_id' => user()->getKey(), 'user_id' => $target->getKey()]);

    $acme->links()->create(['source' => 'erp', 'external_id' => 'C-1']);

    $transfer = TransferModel::factory()->create([
        'type' => 'import_members',
        'status' => 'awaiting_confirmation',
        'organization_id' => $acme->id,
        'requested_by' => user()->getKey(),
    ]);

    return compact('acme', 'target', 'ops', 'invitation', 'role', 'assignment', 'impersonation', 'transfer');
}

/**
 * Routes and tools any signed-in user may use: import templates hold no data.
 */
const OPEN_TO_SIGNED_IN = ['imports.templates.show', 'ShowImportTemplateTool', 'transfers.template'];

/**
 * Every JSON API route: [method, name, parameters, body].
 *
 * @return array<string, array{0: string, 1: string, 2: Closure(array<string, mixed>): array<int, mixed>, 3: array<string, mixed>}>
 */
function apiRoutes(): array
{
    $user = fn (array $w): array => [$w['target']->getRouteKey()];
    $org = fn (): array => ['acme'];
    $team = fn (): array => ['acme', 'ops'];
    $none = fn (): array => [];

    return [
        'users.index' => ['GET', 'roster.users.index', $none, []],
        'users.store' => ['POST', 'roster.users.store', $none, ['name' => 'N', 'email' => 'n@example.com']],
        'users.show' => ['GET', 'roster.users.show', $user, []],
        'users.update' => ['PATCH', 'roster.users.update', $user, ['name' => 'Renamed']],
        'users.destroy' => ['DELETE', 'roster.users.destroy', $user, []],
        'users.restore' => ['POST', 'roster.users.restore', $user, []],
        'users.purge' => ['DELETE', 'roster.users.purge', $user, []],
        'users.profile.update' => ['PATCH', 'roster.users.profile.update', $user, ['bio' => 'x']],
        'users.suspend' => ['POST', 'roster.users.suspend', $user, []],
        'users.deactivate' => ['POST', 'roster.users.deactivate', $user, []],
        'users.reactivate' => ['POST', 'roster.users.reactivate', $user, []],
        'users.approve' => ['POST', 'roster.users.approve', $user, []],
        'users.reject' => ['POST', 'roster.users.reject', $user, []],
        'users.context.update' => ['PUT', 'roster.users.context.update', $user, ['organization' => 'acme']],
        'users.domain-join' => ['POST', 'roster.users.domain-join', $user, []],
        'organizations.index' => ['GET', 'roster.organizations.index', $none, []],
        'organizations.store' => ['POST', 'roster.organizations.store', $none, ['name' => 'New', 'owner' => 1]],
        'organizations.show' => ['GET', 'roster.organizations.show', $org, []],
        'organizations.update' => ['PATCH', 'roster.organizations.update', $org, ['name' => 'Renamed']],
        'organizations.destroy' => ['DELETE', 'roster.organizations.destroy', $org, []],
        'organizations.restore' => ['POST', 'roster.organizations.restore', $org, []],
        'organizations.purge' => ['DELETE', 'roster.organizations.purge', $org, []],
        'organizations.transfer' => ['POST', 'roster.organizations.transfer', $org, ['user' => 1]],
        'organizations.sync' => ['PUT', 'roster.organizations.sync', fn (): array => ['erp', 'C-2'], ['name' => 'Initech']],
        'organizations.sync-many' => ['POST', 'roster.organizations.sync-many', $none, ['records' => [['source' => 'erp', 'external_id' => 'C-3', 'name' => 'Globex']]]],
        'organizations.links.update' => ['PUT', 'roster.organizations.links.update', fn (): array => ['acme', 'crm'], ['external_id' => 'X-1']],
        'organizations.links.destroy' => ['DELETE', 'roster.organizations.links.destroy', fn (): array => ['acme', 'erp'], []],
        'members.index' => ['GET', 'roster.organizations.members.index', $org, []],
        'members.store' => ['POST', 'roster.organizations.members.store', $org, ['user' => 1]],
        'members.destroy' => ['DELETE', 'roster.organizations.members.destroy', fn (array $w): array => ['acme', $w['target']->getRouteKey()], []],
        'teams.index' => ['GET', 'roster.organizations.teams.index', $org, []],
        'teams.store' => ['POST', 'roster.organizations.teams.store', $org, ['name' => 'New']],
        'teams.show' => ['GET', 'roster.organizations.teams.show', $team, []],
        'teams.update' => ['PATCH', 'roster.organizations.teams.update', $team, ['name' => 'Renamed']],
        'teams.destroy' => ['DELETE', 'roster.organizations.teams.destroy', $team, []],
        'teams.members.store' => ['POST', 'roster.organizations.teams.members.store', $team, ['user' => 1]],
        'teams.members.destroy' => ['DELETE', 'roster.organizations.teams.members.destroy', fn (array $w): array => ['acme', 'ops', $w['target']->getRouteKey()], []],
        'invitations.index' => ['GET', 'roster.organizations.invitations.index', $org, []],
        'invitations.store' => ['POST', 'roster.organizations.invitations.store', $org, ['email' => 'x@example.com']],
        'invitations.revoke' => ['DELETE', 'roster.organizations.invitations.revoke', fn (array $w): array => ['acme', $w['invitation']->id], []],
        'permissions.index' => ['GET', 'roster.permissions.index', $none, []],
        'permissions.store' => ['POST', 'roster.permissions.store', $none, ['name' => 'reports.view']],
        'permissions.update' => ['PATCH', 'roster.permissions.update', fn (): array => ['invoices.edit'], ['description' => 'x']],
        'permissions.destroy' => ['DELETE', 'roster.permissions.destroy', fn (): array => ['invoices.edit'], []],
        'roles.index' => ['GET', 'roster.roles.index', $none, []],
        'roles.store' => ['POST', 'roster.roles.store', $none, ['name' => 'R', 'scope' => 'global']],
        'roles.show' => ['GET', 'roster.roles.show', fn (array $w): array => [$w['role']->id], []],
        'roles.update' => ['PATCH', 'roster.roles.update', fn (array $w): array => [$w['role']->id], ['name' => 'R2']],
        'roles.destroy' => ['DELETE', 'roster.roles.destroy', fn (array $w): array => [$w['role']->id], []],
        'users.roles.index' => ['GET', 'roster.users.roles.index', $user, []],
        'users.roles.store' => ['POST', 'roster.users.roles.store', $user, ['role' => 'x']],
        'users.roles.destroy' => ['DELETE', 'roster.users.roles.destroy', fn (array $w): array => [$w['target']->getRouteKey(), $w['assignment']->id], []],
        'users.permissions' => ['GET', 'roster.users.permissions', $user, []],
        'users.impersonate' => ['POST', 'roster.users.impersonate', $user, ['reason' => 'Ticket 1']],
        'impersonations.index' => ['GET', 'roster.impersonations.index', $none, []],
        'impersonations.destroy' => ['DELETE', 'roster.impersonations.destroy', fn (array $w): array => [$w['impersonation']->id], []],
        'imports.store' => ['POST', 'roster.imports.store', $none, ['type' => 'import_members', 'organization' => 'acme', 'content' => "email\nx@example.com"]],
        'imports.templates.show' => ['GET', 'roster.imports.templates.show', fn (): array => ['import_members'], []],
        'imports.confirm' => ['POST', 'roster.imports.confirm', fn (array $w): array => [$w['transfer']->id], []],
        'exports.store' => ['POST', 'roster.exports.store', $none, ['type' => 'export_members', 'organization' => 'acme']],
        'transfers.index' => ['GET', 'roster.transfers.index', $none, ['organization' => 'acme']],
        'transfers.show' => ['GET', 'roster.transfers.show', fn (array $w): array => [$w['transfer']->id], []],
        'transfers.destroy' => ['DELETE', 'roster.transfers.destroy', fn (array $w): array => [$w['transfer']->id], []],
        'transfers.download' => ['GET', 'roster.transfers.download', fn (array $w): array => [$w['transfer']->id], []],
    ];
}
