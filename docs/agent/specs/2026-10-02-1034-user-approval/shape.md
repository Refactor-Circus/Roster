# User Approval — Shaping Notes

## Scope

Make the starting status of new users configurable, so a site can require manual acceptance of new accounts.

## Decisions

- **A new `pending` status,** with `ApproveUserAction` (to active) and `RejectUserAction` (deactivates, with a reason).
- **Who decides the starting status** (the user's answer): "for sso and scim, let the org choose. on the create user screen (admin) there should be a choice. self register is a config option".
  - Self-registration: `roster.users.registration_status`, applied through Laravel's `Registered` event.
  - Admin create: a `status` input on `CreateUserAction`.
  - SSO just-in-time accounts and SCIM: the organization setting `provisioned_status`.
  - CSV imports aren't covered; they create active users.
- **Rejecting** deactivates with a reason.
- **Notifications** to approvers and to the user, "make both options configurable".
- **Defaults I chose:**
  - a global `roster.users.approve` permission, so organization admins can't approve;
  - pending users are blocked by `roster.active` and by SSO sign-in;
  - reactivating a pending user is refused.

## Decisions made during implementation

- **SSO:** a refused sign-in throws inside the sign-in transaction, which would have rolled back a just-in-time account created as pending. Pending users are therefore refused *after* the transaction commits: the account, its membership and its SSO link are kept, and the failure is audited with reason `pending`. Other inactive statuses are still refused inside, as before.
- **The `Registered` listener** only moves an account to pending when its profile is active and its status has never been changed (`status_changed_at` null). It never undoes an admin's decision.
- **Approvers** are users with a global role that grants `roster.users.approve` or is super, plus verified configured super-admins. Pending users never get their own approval email.
- **Mail:** sent on demand to the user's email address, like invitations, so user models don't need `Notifiable`. The approver email links to the user's Atrium page when Atrium is installed.
- **Atrium:** the approval card shows whenever the user is pending, and the server authorizes the action. That matches the other user-page cards, which render with Roster's authorization off too. The "Users by status" widget now counts every status, so it includes pending.
- **`provisioned_status`** is an organization setting next to `auto_join`, available on create and update (HTTP, MCP and the Atrium Settings tab). It isn't part of organization sync records, which nobody asked for.

## Context

- **Visuals:** None.
- **References:** `Actions/Concerns/ChangesStatus.php`, `SuspendUserAction` / `ReactivateUserAction`, `Notifications/InvitationNotification.php`, `Listeners/JoinOrganizationsOnVerified.php`, and `OrganizationRules` (`auto_join`).
- **Product alignment:** a Phase 3 follow-up requested by the user.
