# Roster — Configurable default user status and manual approval

## Context

Some sites want new accounts to wait for manual acceptance instead of becoming active straight away. Today every user starts active, and a user with no profile row counts as active. Roster also doesn't own sign-up: apps register users through their own code (Breeze, Fortify, Jetstream), so a default status only reaches self-registered users if Roster hooks into registration.

Goal:
- a new **pending** status;
- approve and reject Actions;
- the starting status chosen per creation path, as the user specified:
  - **Self-registration:** a config option.
  - **SSO first sign-in and SCIM:** chosen by each organization.
  - **Admin "create user" screen** (and API/MCP, for parity): a choice on the form.
- optional notifications to approvers and to the user.

## Decisions (from shaping)

- **New status:** `UserStatus::Pending` ("Awaiting approval").
  - `ApproveUserAction` moves pending → active.
  - `RejectUserAction` moves pending → deactivated, with an optional reason. The account is kept, the email stays taken, and it can be reactivated later.
- **Starting status per path:**
  - **Self-registration:** `roster.users.registration_status` (`active` | `pending`, default `active`). A listener on `Illuminate\Auth\Events\Registered` gives the new user a profile with that status, or updates the profile when its status was never changed. It only ever moves a user from active to pending, never the reverse.
  - **Admin create** (`CreateUserAction`, so Atrium, API and MCP): an optional `status` input (`active` | `pending`, default `active`). The Atrium create form gets a select.
  - **SSO just-in-time accounts and SCIM-created accounts:** a new organization setting `provisioned_status` (`active` | `pending`, default `active`), set like `auto_join`. It only affects accounts *created* through that organization. Existing accounts that are linked keep their status.
  - **CSV imports** keep creating active users, since this wasn't asked for. That's noted in the docs.
- **Notifications,** each toggled in config:
  - `roster.users.approvals.notify_approvers` (default `true`): `UserAwaitingApprovalNotification` goes to everyone holding `roster.users.approve` globally (super-admins included), whenever a user becomes pending.
  - `roster.users.approvals.notify_user` (default `true`): `UserApprovedNotification` or `UserRejectedNotification` goes to the user's email, sent on demand like invitations.

## Decisions I made (defaults, open to change)

- **Permission:** a new global permission, `roster.users.approve`, for approving and rejecting. Status changes are already global (`roster.users.manage-status`), so this keeps acceptance a site decision. When an organization picks `pending` for its SSO/SCIM accounts, the site's approvers accept those accounts; organization admins can't. It's added to `BuiltInRoles` and to `roster:sync-permissions`, and isn't part of the organization admin role.
- **What pending users can do:**
  - The `roster.active` middleware refuses them with its own message ("Your account is awaiting approval").
  - SSO sign-in refuses them, as it already does for inactive users, with the same message.
  - Impersonating them is refused, as already happens for any non-active user.
- **Allowed transitions:**
  - `ReactivateUserAction` is refused for pending users; approval is the path.
  - Suspend and deactivate are still allowed.
  - Approve only works from pending, and reject only works from pending.
- **Audit:** `user.approved` and `user.rejected` entries come from the Action events as usual. Becoming pending through registration or provisioning shows up in the `user.created` / `profile` changes.

## Task 1: Spec documentation

`agent-os/specs/2026-10-02-HHMM-user-approval/` with `plan.md`, `shape.md`, `standards.md` and `references.md`. Add a line to the roadmap (Phase 3).

## Task 2: Status, config, schema

- `src/Enums/UserStatus.php`: add `Pending`, its label, and its badge (warning) in `Atrium/Badges.php`.
- `config/roster.php`: `users.registration_status`, `users.approvals.notify_approvers` and `users.approvals.notify_user`, documented in the Users banner.
- Organizations migration (edited in place; the package is unreleased): `provisioned_status` string(16), default `active`. Add it to `Organization` (fillable, `@property`), to `OrganizationRules::settingsRules` (`in:active,pending`), to the Create/Update Organization Actions (written alongside `auto_join`), and to `OrganizationResource`.

## Task 3: Actions and events

- `ApproveUserAction(Model $user, array $data = [], ?Model $actor)`:
  - guards that the user is pending (field-keyed `ValidationException` otherwise);
  - sets the status to active through `ChangesStatus::changeStatus`;
  - fires `UserApproving` / `UserApproved` events;
  - notifies the user when that's enabled.
- `RejectUserAction(Model $user, array $data = ['reason' => ?], ?Model $actor)`: pending → deactivated with the reason, `UserRejecting` / `UserRejected` events, and a user notification.
- `ChangesStatus`: `guardTransition` refuses to *reactivate* a pending user. Approving and rejecting use their own guard.
- `CreateUserAction`: an optional `status` rule (`in:active,pending`). The profile is created with it. When the user ends up pending, approvers are notified (one shared helper, `Support/Approvals::pending($user)`).
- `SsoLoginAction` just-in-time path and `ScimUsers::create`: pass the organization's `provisioned_status` as `status`.
- **Registration listener** `Listeners/ApplyRegistrationStatus` on `Registered`, registered in the provider: when `registration_status` is `pending` and the user's profile is missing or still active and never changed, set it to pending and call `Approvals::pending()`.
- **`Support/Approvals`:**
  - `approvers()`: users with a global role containing `roster.users.approve` or a super role, plus verified configured super-admins, in one query per source;
  - `pending($user)`: sends the approver notification when enabled.
- **Notifications** in `src/Notifications/` (mail), following `InvitationNotification`'s pattern, with lang strings.

## Task 4: Surfaces

- **HTTP:** `POST roster/users/{user}/approve` and `POST roster/users/{user}/reject` (FormRequests with ability `roster.users.approve`). `StoreUserRequest` accepts `status` through the Action's rules.
- **MCP:** `approve-user-tool` and `reject-user-tool`. `create-user-tool` gets a `status` schema field, and the organization create/update tools get `provisioned_status`.
- **Atrium:**
  - Users index: the status filter already lists every enum case, so it gains "Awaiting approval".
  - User page: when pending, an "Awaiting approval" card with Approve and Reject buttons (a reason field on reject), shown to holders of `roster.users.approve`.
  - Create-user form: a status select (Active / Awaiting approval).
  - Organization settings tab: a "New accounts from SSO/SCIM" select.
  - The "Users by status" widget counts pending users.
  - Use only classes in Atrium's CSS or `partials/styles` (StylesTest enforces this).
- **UserResource:** status is already serialized, so `pending` appears automatically.

## Task 5: Tests

- **Actions:** approve and reject transitions and their guards, reactivate refused for pending, a self-approve guard (`ChangesStatus` already stops self-changes, so check that approving yourself is refused), notifications sent or not per config, audit entries.
- **Registration:** the `Registered` listener makes users pending only when configured, and never downgrades a user whose status was already changed.
- **Starting status per path:** the admin create `status` field; SSO just-in-time and SCIM respect `provisioned_status`; linked existing accounts keep their status.
- **Enforcement:** `roster.active` blocks pending users with the approval message; SSO sign-in refuses them.
- **Surfaces:** HTTP and MCP approve/reject; the authorization matrix (fixtures, tool args, screens, a new permission); the docs test; the Atrium page buttons and widget count; a browser test where an admin approves a pending user from the user page.

## Task 6: Docs

- README: an "Approving new users" section covering the config, per-organization provisioning, the Actions, notifications and the middleware behaviour; routes and tools added to the tables.
- CHANGELOG, Boost skill, spec decisions, memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- **Workbench:**
  1. Set `roster.users.registration_status=pending`.
  2. Create a user from Atrium with "Awaiting approval": they're blocked by `roster.active`, and the approver gets mail (log mailer).
  3. Approve from the user page: the user is active and notified.
  4. Set Acme's "New accounts from SSO/SCIM" to pending, provision a SCIM user, and check they arrive pending.
