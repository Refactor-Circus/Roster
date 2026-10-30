<?php

declare(strict_types=1);

require_once __DIR__.'/fixtures.php';

use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\AcceptInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\DeclineInvitationTool;
use RefactorCircus\Roster\Mcp\RosterServer;
use RefactorCircus\Roster\Mcp\Tools\ListRosterHistoryTool;

/**
 * Arguments that name existing records for every tool.
 *
 * @return array<class-string, Closure(array<string, mixed>): array<string, mixed>>
 */
function toolArguments(): array
{
    $user = fn (array $w): array => ['user' => $w['target']->getRouteKey()];
    $org = fn (): array => ['organization' => 'acme'];
    $team = fn (): array => ['organization' => 'acme', 'team' => 'ops'];

    return collect(RosterServer::TOOLS)
        // Answering an invitation needs no permission; the history tool is not
        // offered without an audit log (tests/Modes/Keen covers it).
        ->reject(fn (string $tool): bool => in_array($tool, [AcceptInvitationTool::class, DeclineInvitationTool::class, ListRosterHistoryTool::class], true))
        ->mapWithKeys(fn (string $tool): array => [$tool => match (class_basename($tool)) {
            'ShowUserTool', 'UpdateUserTool', 'DeleteUserTool', 'UpdateProfileTool', 'SuspendUserTool', 'DeactivateUserTool',
            'ReactivateUserTool', 'ApproveUserTool', 'RejectUserTool', 'RestoreUserTool', 'PurgeUserTool', 'SwitchContextTool', 'JoinByDomainTool', 'ListRoleAssignmentsTool', 'AssignRoleTool',
            'ListUserPermissionsTool' => $user,
            'RevokeRoleTool' => fn (array $w): array => $user($w) + ['assignment' => $w['assignment']->id],
            'ShowOrganizationTool', 'UpdateOrganizationTool', 'DeleteOrganizationTool', 'RestoreOrganizationTool', 'PurgeOrganizationTool', 'TransferOwnershipTool', 'ListMembersTool',
            'AddMemberTool', 'ListTeamsTool', 'CreateTeamTool', 'ListInvitationsTool', 'CreateInvitationTool' => $org,
            'RemoveMemberTool' => fn (array $w): array => $org() + $user($w),
            'SyncOrganizationTool' => fn (): array => ['source' => 'erp', 'external_id' => 'C-2', 'name' => 'Initech'],
            'SyncOrganizationsTool' => fn (): array => ['records' => [['source' => 'erp', 'external_id' => 'C-3', 'name' => 'Globex']]],
            'LinkOrganizationTool' => fn (): array => ['organization' => 'acme', 'source' => 'crm', 'external_id' => 'X-1'],
            'UnlinkOrganizationTool' => fn (): array => ['organization' => 'acme', 'source' => 'erp'],
            'RevokeInvitationTool' => fn (array $w): array => $org() + ['invitation' => $w['invitation']->id],
            'ShowTeamTool', 'UpdateTeamTool', 'DeleteTeamTool', 'AddTeamMemberTool' => $team,
            'RemoveTeamMemberTool' => fn (array $w): array => $team() + $user($w),
            'ShowRoleTool', 'UpdateRoleTool', 'DeleteRoleTool' => fn (array $w): array => ['role' => $w['role']->id],
            'UpdatePermissionTool', 'DeletePermissionTool' => fn (): array => ['name' => 'invoices.edit'],
            'StopImpersonationTool' => fn (array $w): array => ['impersonation' => $w['impersonation']->id],
            'StartImpersonationTool' => fn (array $w): array => $user($w) + ['reason' => 'Ticket 1'],
            'ShowImportTemplateTool' => fn (): array => ['type' => 'import_users'],
            'StartImportTool' => fn (): array => ['type' => 'import_members', 'organization' => 'acme', 'content' => "email\nx@example.com"],
            'StartExportTool' => fn (): array => ['type' => 'export_members', 'organization' => 'acme'],
            'ListTransfersTool' => $org,
            'ConfirmImportTool', 'ShowTransferTool', 'CancelTransferTool' => fn (array $w): array => ['transfer' => $w['transfer']->id],
            default => fn (): array => [],
        }])
        ->all();
}

it('refuses every tool to guests and to users without the permission', function (string $tool): void {
    $world = authorizationWorld();
    $arguments = toolArguments()[$tool]($world);

    mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);

    $this->actingAs(user());

    in_array(class_basename($tool), OPEN_TO_SIGNED_IN, true)
        ? mcpTool($tool, $arguments)->assertOk()
        : mcpTool($tool, $arguments)->assertHasErrors(['Unauthorized.']);
})->with(fn (): array => array_keys(toolArguments()));

it('lets a super-admin past authorization on every tool', function (string $tool): void {
    $world = authorizationWorld();
    $admin = user();
    grant($admin, 'super-admin');
    $this->actingAs($admin);

    mcpTool($tool, toolArguments()[$tool]($world))->assertDontSee('Unauthorized.');
})->with(fn (): array => array_keys(toolArguments()));

it('needs a signed-in invitee to answer invitations', function (): void {
    mcpTool(AcceptInvitationTool::class, ['token' => 'x'])->assertHasErrors(['Unauthorized.']);
    mcpTool(DeclineInvitationTool::class, ['token' => 'x'])->assertHasErrors(['Unauthorized.']);
});

it('offers no history tool without an audit log', function (): void {
    grant($admin = user(), 'super-admin');
    $this->actingAs($admin);

    // Not offered at all, so not even a super-admin reaches it.
    mcpTool(ListRosterHistoryTool::class)->assertHasErrors();
});
