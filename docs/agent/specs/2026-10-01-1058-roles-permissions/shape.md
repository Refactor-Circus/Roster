# Roles & Permissions — Shaping Notes

## Scope

Phase 1 MVP, feature 3. Database-backed roles and permissions at global, organization and team scope, wired into Laravel's Gate. Also turns on authorization for every Roster surface (HTTP, MCP, Atrium), closing the "no authorization" gap features 1–2 shipped with.

## Decisions

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


## Context

- **Visuals:** None.
- **References:** features 1–2 (this package), Impex `src/Access/Authorizer.php`.
- **Product alignment:** roadmap Phase 1 item 3. Makes the HTTP/MCP surfaces safe to enable.
- **Decided without asking:** config super-admin emails count only for verified emails (security).

## Standards Applied

- Same set as feature 2 (see standards.md). Domain guards carry the escalation and scope checks; `config-docs` covers the security banners for `authorization` and `super_admins`.

## Decisions made during implementation

- **Owners skip global-only permissions.** `Permissions::GLOBAL_ONLY_PREFIXES` (`roster.users.`, `roster.organizations.create`, `atrium.`) are excluded from an owner's implicit grant. Owning an organization must not grant user administration across the whole app.
- **Super-admins pass every Gate check**, the app's included. Gate::before returns `true` for them before even looking at the ability.
- **Requests declare `ability()` / `scope()` / `self()`.** The base HTTP and MCP requests authorize through `Access\Authorizer`, and an arch rule requires `ability()` on every request class. Invitation answers declare `''` and override `authorize()` to require only a signed-in invitee.
- **HTTP guests get 401 and signed-in users without the permission get 403** (`failedAuthorization`). MCP returns "Unauthorized." for both.
- **Scope from input before validation:** `Support\Scopes::fromInput()` resolves `organization`/`team` slugs for checks that run before validation (create role, assign role, list roles). Unknown slugs fall back to global and then fail validation.
- **Revoking is guarded like granting.** The actor must hold the role's permissions in that scope, and only super-admins revoke super roles.
- **Sync never overwrites.** `BuiltInRoles::sync()` only adds missing permissions and roles, so admin edits to built-in roles survive upgrades.
- **Tests:** feature 1–2 surface suites run with `roster.authorization = false`, as Impex's do. `tests/Authorization` (281 cases) runs with it on: every API route, MCP tool and key Atrium screen is checked for guest, unprivileged and super-admin. A coverage test fails if an API route is missing from the matrix.
- **The Atrium gate test runs with the real Roster-defined gate** (`AuthorizationTestCase::$openAtrium = false`). A test claiming "host definition wins" was dropped: defining after boot can't prove ordering.
