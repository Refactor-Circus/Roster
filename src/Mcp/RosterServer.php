<?php

declare(strict_types=1);

namespace RefactorCircus\Roster\Mcp;

use Laravel\Mcp\Server\Attributes\Instructions;
use Laravel\Mcp\Server\Attributes\Name;
use Laravel\Mcp\Server\Attributes\Version;
use Laravel\Mcp\Server\Tool;
use Laravel\Mcp\Server\Tools\ToolSearch;
use RefactorCircus\Keystone\Mcp\Server;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools\ListImpersonationsTool;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools\StartImpersonationTool;
use RefactorCircus\Roster\Domains\Impersonation\Mcp\Tools\StopImpersonationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\AcceptInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\CreateInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\DeclineInvitationTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\ListInvitationsTool;
use RefactorCircus\Roster\Domains\Invitation\Mcp\Tools\RevokeInvitationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\AddMemberTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\CreateOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\DeleteOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\JoinByDomainTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\LinkOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ListMembersTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ListOrganizationsTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\PurgeOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\RemoveMemberTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\RestoreOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\ShowOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\SwitchContextTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\SyncOrganizationsTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\SyncOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\TransferOwnershipTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\UnlinkOrganizationTool;
use RefactorCircus\Roster\Domains\Organization\Mcp\Tools\UpdateOrganizationTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\CreatePermissionTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\DeletePermissionTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\ListPermissionsTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\ListUserPermissionsTool;
use RefactorCircus\Roster\Domains\Permission\Mcp\Tools\UpdatePermissionTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\AssignRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\CreateRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\DeleteRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\ListRoleAssignmentsTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\ListRolesTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\RevokeRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\ShowRoleTool;
use RefactorCircus\Roster\Domains\Role\Mcp\Tools\UpdateRoleTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\AddTeamMemberTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\CreateTeamTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\DeleteTeamTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\ListTeamsTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\RemoveTeamMemberTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\ShowTeamTool;
use RefactorCircus\Roster\Domains\Team\Mcp\Tools\UpdateTeamTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\CancelTransferTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ConfirmImportTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ListTransfersTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ShowImportTemplateTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\ShowTransferTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\StartExportTool;
use RefactorCircus\Roster\Domains\Transfer\Mcp\Tools\StartImportTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ApproveUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\CreateUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\DeactivateUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\DeleteUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ListUsersTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\PurgeUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ReactivateUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\RejectUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\RestoreUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\ShowUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\SuspendUserTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\UpdateProfileTool;
use RefactorCircus\Roster\Domains\User\Mcp\Tools\UpdateUserTool;
use RefactorCircus\Roster\Mcp\Tools\ListRosterHistoryTool;

#[Name('Roster')]
#[Version('1.0.0')]
#[Instructions(
    'Manage this application\'s users. A user is identified by the `id` returned from list-users-tool. '.
    'Each user has a profile (display name, avatar, timezone, locale, bio, meta) and a status: active, '.
    'suspended (a temporary block) or deactivated (a closed account that can be reopened). '.
    'Suspended and deactivated users are blocked from routes guarded by the roster.active middleware; '.
    'reactivate-user-tool returns either to active. You cannot suspend, deactivate or delete yourself. '.
    'Organizations (by slug) are tenants: users join them as members, and teams (slug unique within the '.
    'organization) group members. Each organization has one owner, who cannot be removed; transfer-ownership-tool '.
    'moves it. Invite people by email with create-invitation-tool; the recipient accepts through the emailed link. '.
    'A user\'s current organization and team are set with switch-context-tool. '.
    'Roles bundle permissions and are assigned globally, in an organization, or on a team; a user\'s permissions in '.
    'a scope are the union of their global, organization and team roles, and an organization\'s owner holds every '.
    'organization permission. Every tool needs a permission (e.g. roster.users.update) - list-user-permissions-tool '.
    'shows what a user holds. You can only grant permissions you hold yourself. '.
    'When the refactor-circus/keen audit log is installed, every change is recorded there; list-roster-history-tool reads Roster\'s history. '.
    'Bulk changes go through CSV (show-import-template-tool gives each import type\'s columns): start-import-tool previews every row without changing anything, and only '.
    'confirm-import-tool applies it; start-export-tool builds a CSV whose download link show-transfer-tool returns. '.
    'Organizations kept in an external system (ERP, CRM) are synced by their id there with sync-organization-tool or '.
    'sync-organizations-tool, and found with list-organizations-tool (source, external_id, account_number).',
)]
final class RosterServer extends Server
{
    /**
     * Every tool the server offers, behind ToolSearch.
     *
     * @var array<int, class-string<Tool>>
     */
    public const array TOOLS = [
        // Users
        ListUsersTool::class,
        ShowUserTool::class,
        CreateUserTool::class,
        UpdateUserTool::class,
        DeleteUserTool::class,
        RestoreUserTool::class,
        PurgeUserTool::class,

        // Profile
        UpdateProfileTool::class,

        // Status
        SuspendUserTool::class,
        DeactivateUserTool::class,
        ReactivateUserTool::class,
        ApproveUserTool::class,
        RejectUserTool::class,

        // Context
        SwitchContextTool::class,
        JoinByDomainTool::class,

        // Organizations
        ListOrganizationsTool::class,
        ShowOrganizationTool::class,
        CreateOrganizationTool::class,
        UpdateOrganizationTool::class,
        DeleteOrganizationTool::class,
        RestoreOrganizationTool::class,
        PurgeOrganizationTool::class,
        TransferOwnershipTool::class,
        SyncOrganizationTool::class,
        SyncOrganizationsTool::class,
        LinkOrganizationTool::class,
        UnlinkOrganizationTool::class,

        // Members
        ListMembersTool::class,
        AddMemberTool::class,
        RemoveMemberTool::class,

        // Teams
        ListTeamsTool::class,
        ShowTeamTool::class,
        CreateTeamTool::class,
        UpdateTeamTool::class,
        DeleteTeamTool::class,
        AddTeamMemberTool::class,
        RemoveTeamMemberTool::class,

        // Invitations
        ListInvitationsTool::class,
        CreateInvitationTool::class,
        RevokeInvitationTool::class,
        AcceptInvitationTool::class,
        DeclineInvitationTool::class,

        // Roles and permissions
        ListPermissionsTool::class,
        CreatePermissionTool::class,
        UpdatePermissionTool::class,
        DeletePermissionTool::class,
        ListRolesTool::class,
        ShowRoleTool::class,
        CreateRoleTool::class,
        UpdateRoleTool::class,
        DeleteRoleTool::class,
        ListRoleAssignmentsTool::class,
        AssignRoleTool::class,
        RevokeRoleTool::class,
        ListUserPermissionsTool::class,

        // Impersonation
        StartImpersonationTool::class,
        ListImpersonationsTool::class,
        StopImpersonationTool::class,

        // History, from the suite-wide audit log (refactor-circus/keen)
        ListRosterHistoryTool::class,

        // CSV import and export
        ShowImportTemplateTool::class,
        StartImportTool::class,
        ConfirmImportTool::class,
        StartExportTool::class,
        ListTransfersTool::class,
        ShowTransferTool::class,
        CancelTransferTool::class,
    ];

    /**
     * @var array<class-string<ToolSearch>, array<int, class-string<Tool>|Tool>>
     */
    protected array $tools = [
        ToolSearch::class => self::TOOLS,
    ];
}
