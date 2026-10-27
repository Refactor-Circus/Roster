# Roster — Soft deletes for users and organizations

## Context

Deleting a user or an organization is permanent today. The user wants both to be soft deletes: recoverable, with a way to restore, a way to delete permanently, and an optional automatic purge. Users belong to the host app's model, which Roster can't change. Organizations are Roster's own model.

## Decisions (from shaping)

- **Users** use Laravel's `SoftDeletes` on the user model.
  - Roster's bundled `RefactorCircus\Roster\Models\User` gets the trait. Host apps add it to their own model, and the README says how.
  - Laravel then hides deleted users everywhere, including sign-in, because the Eloquent user provider ignores trashed users.
  - `DeleteUserAction` already keeps memberships, roles and the profile for soft-deletable models, so a restore brings everything back.
  - **A model without the trait** keeps today's permanent delete. The Danger zone warning then says it's permanent.
- **Organizations** use `SoftDeletes` on `Organization`. A soft delete keeps everything (teams, memberships, roles, SSO, SCIM, links), and **its slug and domains stay reserved** so a restore always works.
- **Recovery:**
  - **Restore** for users and organizations.
  - **Delete permanently** for trashed records, with its own permissions.
  - **`roster:purge-deleted`** permanently deletes records trashed longer than `roster.deletes.retention_days`. Null (the default) means **keep forever**.
- **Keep the warning.** Deleting is still in the Danger zone, with a warning and an "I understand" confirmation.
  - The wording now says the record moves to Deleted and can be restored. It says it's permanent when the user model can't soft-delete.
  - "Delete permanently" lives in its own Danger zone on the deleted record's page, with the same warning and confirmation.

## Decisions I made (defaults, open to change)

- **Permissions:**
  - Restore uses the same permission as deleting (`roster.users.delete` / `roster.organizations.delete`).
  - Deleting permanently needs new permissions, `roster.users.force-delete` and `roster.organizations.force-delete`. Neither is part of the organization admin role.
- **What a soft-deleted organization still does:** nothing.
  - **Slug lookups** go through `Organization::query()`, whose soft-delete scope excludes it. So the API, MCP, Atrium pages and its SCIM base URL return 404.
  - **Explicit guards** cover everything reached through relationships:
    - a SCIM token or SSO connection whose organization is trashed is refused;
    - domain auto-join skips trashed organizations (their domains are still reserved);
    - a user's current organization falls back past a trashed one (`Roster::organization()` / `preload()`);
    - a user's memberships in Atrium and the API leave it out.
- **Soft-deleted users** disappear from member, team, role-assignment and SCIM lists (`whereHas('user')`), and can't be impersonated. Their audit entries stay, with no label change.
- **Purging** an organization (permanent delete) runs the existing database cascades and clears users' current-organization pointers. Purging a user runs today's hard-delete cleanup: owned personal organizations, memberships, role assignments and profile.
- **Owners:** soft-deleting a user who owns shared organizations is still refused, as today, because the owner can't vanish while the organization stays live. Purging keeps the same rule.

## Task 1: Spec documentation

`agent-os/specs/2026-10-02-HHMM-soft-deletes/` with `plan.md`, `shape.md`, `standards.md` and `references.md`, plus a line in the roadmap.

## Task 2: Schema, models, config

- **Organizations migration** (edited in place; the package is unreleased): `$table->softDeletes()`. The bundled users migration (`database/migrations/users`) also gets `softDeletes()`.
- `Organization` uses `SoftDeletes`, with a `@property deleted_at`.
- The bundled `Models\User` uses `SoftDeletes`.
- **Workbench:** its `User` model and users migration get `SoftDeletes`, so the demo and tests cover the recoverable path.
- **New `Support/Users::softDeletes(): bool`** (whether the model uses the trait), plus `query()` helpers `withTrashed` / `onlyTrashed` that are guarded when the trait is absent.
- **`config/roster.php`:** `deletes.retention_days` (null = keep forever), documented in a banner.

## Task 3: Actions and events

| Action | Notes |
|---|---|
| `DeleteUserAction` | Unchanged behaviour (soft when possible). The doc comment notes that restore works for soft-deletable models. |
| `RestoreUserAction` | Only for trashed, soft-deletable users. Events `UserRestoring` / `UserRestored`. |
| `ForceDeleteUserAction` | Trashed (or, without the trait, any) user: today's hard-delete cleanup, then `forceDelete()`. Owner and self guards. Events `UserForceDeleting` / `UserForceDeleted`. |
| `DeleteOrganizationAction` | Now a soft delete: keep everything and dispatch the existing events. The force-cascade branch for personal organizations of hard-deleted users moves to the force path. |
| `RestoreOrganizationAction` / `ForceDeleteOrganizationAction` | Trashed only. Force delete clears profile pointers, then `forceDelete()` (database cascades). Events `OrganizationRestoring/-ed` and `OrganizationForceDeleting/-ed`. |
| `ListUsersAction` / `ListOrganizationsAction` | New filter `trashed`: `only` / `with` (users only when soft-deletable). |
| `PurgeDeletedCommand` (`roster:purge-deleted {--days=}`) | Runs the force-delete Actions on users and organizations trashed before the cutoff, in chunks. Prints "Keeping deleted records forever" when retention is null and no `--days` is given. |

- **Guards:**
  - `AuthenticateScimToken` (token's organization trashed → 401);
  - SSO connection lookups and the sign-in path refuse a trashed organization;
  - `JoinOrganizationsByDomainAction` skips trashed organizations;
  - `Roster::organization()` / `preload()` ignore memberships of trashed organizations;
  - `ListMembersAction`, the team-member list, `ListRoleAssignmentsAction` and the user page's memberships use `whereHas('user')` / `whereHas('organization')`.

## Task 4: Surfaces

- **HTTP:**
  - `POST roster/users/{user}/restore` and `DELETE roster/users/{user}/force`;
  - `POST roster/organizations/{organization}/restore` and `DELETE roster/organizations/{organization}/force`;
  - the list `trashed` filter.
  - These routes resolve **trashed** records (`withTrashed` lookups in their requests).
- **MCP:** `restore-user-tool`, `force-delete-user-tool`, `restore-organization-tool` and `force-delete-organization-tool`; the list tools gain `trashed`.
- **Atrium:**
  - The Users and Organizations lists get a "Show: Current / Deleted" filter. Deleted rows have **Restore** in their Actions column and link to the record's page.
  - A deleted user's or organization's page loads with `withTrashed`. It shows a "Deleted <time ago>" banner with **Restore**. Its Danger zone offers **Delete permanently**, with a warning and "I understand". Editing forms are hidden while deleted.
  - Soft-delete Danger zones keep their warning and confirmation, with wording for the recoverable delete. The permanent wording is used when the user model has no `SoftDeletes`.

## Task 5: Tests

- **Users:**
  - soft delete keeps the profile, memberships and roles, and the user can't sign in;
  - restore brings everything back;
  - force delete cleans up;
  - a model without the trait (`TraitlessTestCase` / `PlainUser`) still hard-deletes, and the warning says so.
- **Organizations:**
  - soft delete hides the organization from slug lookups, API, MCP and SCIM (401/404);
  - auto-join, SSO and current context skip it;
  - its slug and domains stay reserved (validation errors);
  - restore works; force delete cascades.
- **Purge command:** respects `retention_days` and `--days`; null keeps everything.
- **Surfaces:** HTTP, MCP and Atrium (the Deleted filter, Restore, the permanent-delete Danger zone); the authorization matrix (new routes, tools, screens and permissions); the docs test and parity.
- **Browser:** delete a user → see them under Deleted → restore.

## Task 6: Docs

- **README:** a "Deleting and restoring" section covering the trait requirement for users, reserved slugs and domains, restore, delete permanently, `roster:purge-deleted` and retention (null = forever); add the routes and tools to the tables.
- CHANGELOG, Boost skill, spec decisions and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- **Workbench:**
  1. Delete Pat from their Danger zone: they show under Users → Deleted.
  2. Restore Pat: memberships are intact.
  3. Delete Globex: its page 404s, and so does its SCIM URL.
  4. Show Deleted, restore, delete again, then **Delete permanently**: gone for good.
  5. Run `roster:purge-deleted --days=0`.
