# Roster — Feature 3: Roles & Permissions

## Context

Roster roadmap, Phase 1, feature 3. Features 1 (users/profiles, spec `2026-10-01-1006-users-profiles`) and 2 (orgs/teams/members, spec `2026-10-01-1034-organizations-teams-members`) shipped with **no authorization**: the HTTP API and MCP are off by default because anyone reaching them could manage users. This slice adds database-backed roles and permissions scoped globally, per organization and per team. It hooks them into Laravel's Gate (`$user->can()`, `@can`) and authorizes every Roster surface with them. Feature 4 (surface hardening) remains after this.

Existing pieces to reuse:
- Actions, events and guards: `src/Actions/*`, `Actions/Concerns/ManagesMemberships.php`, `Events/Action/*`.
- Request layers: `src/Http/Request.php` and `src/Mcp/Request.php`, which currently `authorize()` true and take an `actor()`.
- `src/Roster.php` (context resolution) and `src/Support/Users.php` (`emailVerified`, `findOrFail`).
- Migrations: `Support/UserKey::column`. Atrium: `RosterPlugin`, `Http/Ui/*`. Tests: `tests/Pest.php` (`parityGaps`, `mcpTool`, `organization()`).

## Decisions (from shaping)

- **Scopes: global, organization and team.**
  - Global roles cover managing Roster and the app (e.g. super-admin).
  - Org roles are assigned per organization (admin, member).
  - Team roles are assigned per team (lead).
- **Everything lives in the database.** Permissions are records (`name` like `roster.users.update` or the host's `invoices.edit`). Roles are records that bundle permissions.
  - Roster's built-in permissions and default roles are seeded by migration and re-synced with `roster:sync-permissions`. They're flagged `system`, so they can't be deleted (but their permissions can be edited).
- **Role catalog: shared templates plus org-specific custom roles.**
  - A role with `organization_id = null` is available everywhere in its scope.
  - An organization can define its own org- or team-scoped roles.
- **A check is the union across scopes.**
  - `can(perm)` takes global roles ∪ roles in the current org ∪ roles in the current team.
  - `can(perm, $org)` / `can(perm, $team)` check that scope, and a team check includes its org and global.
  - **The org owner** has every permission within their organization (org and team checks).
  - **A super role** (`roles.super = true`, seeded `super-admin`) passes everything.
- **Gate integration:** `Gate::before` resolves any ability that is a known permission name. It returns `true` when granted and `null` otherwise, so host policies still run. `$user->can('invoices.edit', $organization)` and `@can` work.
- **Super-admin bootstrap, in three ways:**
  - `php artisan roster:grant-super-admin {email}`.
  - Config `roster.super_admins` (emails), honoured **only for verified emails** on `MustVerifyEmail` models, so registering a listed address isn't enough.
  - `AssignRoleAction` from the host's own seeder.
- **Atrium gate:** if the host hasn't defined `viewAtrium`, Roster defines it as the `atrium.view` permission. A host definition always wins.
- **Surface authorization is on** (`roster.authorization`, default `true`).
  - Every HTTP, MCP and Atrium request authorizes its Action's permission in the right scope. Guests are denied.
  - The HTTP API and MCP **stay off by default**, but are safe to enable now. The config banners are updated.
  - Turning `roster.authorization` off restores the earlier open behaviour, and the banner warns against it.
- **Privilege-escalation guard:** an actor can only assign a role, or add permissions to a role, using permissions they hold themselves in that scope. Only super-admins can assign or create super roles. This is enforced in the Actions with field-keyed `ValidationException` errors.
- **Default member role:** on joining an org by any source, members get `roster.roles.default_member` (default `member`; null disables). Owners need no role.

## Permission map (built-ins)

| Permission | Scope | Used by |
|---|---|---|
| `roster.users.view` / `.create` / `.update` / `.delete` / `.manage-status` | global | user Actions. A user may always view themselves, and update their own profile and context |
| `roster.organizations.view` | org (global for list) | List/Show organization |
| `roster.organizations.create` | global | CreateOrganization |
| `roster.organizations.update` / `.delete` / `.transfer` | org | Update/Delete/TransferOwnership |
| `roster.members.view` / `.manage` | org | List/Add/RemoveMember |
| `roster.teams.view` / `.manage` | org or team | team Actions. `teams.manage` at team scope covers seats |
| `roster.invitations.view` / `.manage` | org | invitations. Accept/decline only need the invitee to be authenticated |
| `roster.roles.view` / `.manage` | global, or org for custom roles | role and permission Actions |
| `roster.roles.assign` | scope of the assignment | Assign/RevokeRole |
| `atrium.view` | global | the Atrium gate |

Default roles:
- `super-admin`: global, super.
- `admin`: org, every org-scoped roster permission.
- `member`: org, the view permissions.
- `lead`: team, `roster.teams.manage` and `roster.teams.view`.

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-roles-permissions/` with `plan.md`, `shape.md` (the decisions and permission map), `standards.md` (feature 2's set) and `references.md` (feature 2 files and the Impex `Access/Authorizer.php` pattern).

## Task 2: Schema, models, config, seeding

- **Migration** `2026_01_01_000003_create_roster_role_tables.php`:
  - `roster_permissions`: ulid, `name` unique, `description`, `system` bool, timestamps.
  - `roster_roles`: ulid, `name`, `slug`, `scope` (`global|organization|team`), `organization_id` nullable FK cascade, `description`, `super` bool, `system` bool, timestamps, index(scope, organization_id, slug). Uniqueness is enforced in the Action, because unique indexes treat nulls as distinct.
  - `roster_permission_role`: `role_id` and `permission_id` FKs cascading, primary key on both.
  - `roster_role_assignments`: ulid, `role_id` FK cascade, `user_id` (UserKey), `organization_id` / `team_id` nullable FKs cascading, timestamps, unique(role, user, org, team).
  - Seeds the built-ins through `Support\BuiltInRoles::sync()`, which the command reuses.
- **Models** (final, HasUlids, with factories):
  - `Permission`
  - `Role` (`permissions()` BelongsToMany, `scope` cast to a `RoleScope` enum, `organization`)
  - `RoleAssignment` (role, user, organization, team)
- **`HasRoster`:** add `rosterRoleAssignments()` and `hasRosterPermission($permission, $scope = null)`, plus the dynamic relation for trait-less models.
- **Config:**
  - `authorization` (true)
  - `super_admins` ([])
  - `roles.default_member` (`'member'`)
  - Updated banners on routes and mcp: the API is now authorized, but still keep `auth` middleware.

## Task 3: Authorization core

- **`src/Access/Permissions.php`** (scoped binding), the resolver:
  - `for(Model $user, Organization|Team|null $scope)` returns the effective permission names.
  - `allows($user, $permission, $scope)` checks one, with the owner and super shortcuts.
  - `isSuperAdmin($user)` covers both a super role and a verified config email.
  - `knows($permission)` returns whether a permission name exists.
  - Results are memoized per request.
- **`src/Access/Authorizer.php`**, used by every surface:
  - `check(?Model $actor, string $permission, $scope = null, ?Model $self = null)` returns true when authorization is off. It denies guests, and lets `$self === $actor` through for self-service abilities.
- **Provider wiring:**
  - `Gate::before` for known permissions.
  - A `viewAtrium` definition when the host has none.
  - Commands `roster:grant-super-admin` and `roster:sync-permissions`, console only.
- **Scope inference for Gate:** take the first `Organization` or `Team` argument. Otherwise use a model with an `organization` relation, then the current context from `Roster`.

## Task 4: Actions (`src/Actions/`)

| Group | Actions |
|---|---|
| Permissions | `ListPermissionsAction`, `CreatePermissionAction`, `UpdatePermissionAction` (description), `DeletePermissionAction` (guard: system) |
| Roles | `ListRolesAction` (scope and organization filters; an org listing includes the shared roles), `ShowRoleAction`, `CreateRoleAction` (scope, optional organization, permissions[]; escalation guard), `UpdateRoleAction` (name, description, permissions sync; escalation guard), `DeleteRoleAction` (guard: system) |
| Assignments | `ListRoleAssignmentsAction` (by user or organization), `AssignRoleAction` (user, role, organization?, team?; guards: scope matches role, membership/seat, the org's custom roles only within that org, escalation, super only by a super-admin), `RevokeRoleAction` |
| Introspection | `ListUserPermissionsAction` (effective permissions for a user in a scope, which helps agents and the UI) |

Feature 1–2 updates:
- `ManagesMemberships::join()` assigns the default member role.
- Removing a member or a team seat revokes that scope's assignments.
- Deleting a user removes their assignments.
- Every existing HTTP/MCP request and UI controller method gains its permission check through `Authorizer`, per the permission map. Self-service cases (own profile, own context) stay allowed.

## Task 5: Surfaces

- **HTTP** (`roster.` names):
  - `permissions` CRUD
  - `roles` CRUD, with `?organization=` for custom roles
  - `users/{user}/roles` (index, assign) and `DELETE users/{user}/roles/{assignment}`
  - `users/{user}/permissions` (effective; `?organization=&team=`)
  - Code: requests, controllers, and `PermissionResource` / `RoleResource` (with permission names) / `RoleAssignmentResource`.
  - The base `Http/Request::authorize()` now delegates to a per-request `ability()` + `scope()` through `Authorizer`. Unauthorized gets 403; guests get 401 via `AuthenticationException`.
- **MCP:**
  - One tool per new Action, plus shared schema traits.
  - The base `Mcp/Request::authorize()` follows the same ability/scope pattern and returns "Unauthorized.".
  - The server instructions describe the permission model.
- **Atrium:**
  - A "Roles" nav item: permissions list/create/delete, and roles list with scope filter, create and edit (permission checkboxes).
  - The user page gains a Roles card (assign global/org/team, revoke, effective permissions).
  - The org page gains a "Roles" tab (custom org roles, member assignments).
  - Nav items, widgets and search authorize with `->authorize()` closures through `Authorizer`.
  - Code: `Http/Ui/RoleUiController`, `PermissionUiController`.

## Task 6: Tests

- **Resolver:** `PermissionsTest` (union across scopes, owner, super role, config super-admin verified only, the team check includes org/global, memoization reset).
- **Gate:** `GateTest` (`can()` with org/team/context arguments, unknown abilities fall through to host gates, `@can`, the `viewAtrium` default and host override).
- **Actions:** `RoleActionsTest`, `PermissionActionsTest`, `AssignmentActionsTest` (scope guards, escalation guard, super guard, default member role, revocation on leave).
- **Commands:** `CommandsTest` (grant-super-admin, sync-permissions idempotent).
- **Authorization matrix:** `tests/Feature/Authorization/*`, with a TestCase enabling `roster.authorization`. A dataset over every route and Action covers guest 401/deny, unprivileged 403, permitted 2xx, and self-service allowed, across HTTP, MCP and Atrium.
- **Existing suites:** their TestCase sets `roster.authorization = false` (as Impex does), so feature 1–2 tests stay behaviour-focused.
- **New surfaces:** HTTP, MCP (with parity) and UI tests for the new Actions.
- **Arch:** the parity test covers the new Actions. Add an arch rule that every concrete HTTP and MCP request declares `ability()`.

## Task 7: Docs

- README: the roles & permissions model, Gate usage, super-admin bootstrap, the default roles and permission table, the authorization config, and Atrium.
- CHANGELOG, Boost skill, the spec's implementation decisions, and the memory note.

## Verification

- `composer test` (Herd php84 PATH shim) all green.
- The authorization matrix proves no route or tool is reachable without the mapped permission.
- The parity arch test is still green, and fails if a new MCP request is removed.
- Workbench: `php artisan roster:grant-super-admin admin@example.com`, then `/atrium` loads through the Roster-defined gate. Create a custom org role, assign it, and check `$user->can()` in `tinker`.
