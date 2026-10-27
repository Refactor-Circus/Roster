# Roster — Speed pass (runtime, pages, bulk, tests/CI)

## Context

The user asked where speed can be gained. Three read-only surveys, plus my own check of the key claims, found:
- query multiplication on every request: Gate checks without an organization or team cost about 4–9 queries **each**;
- wasted work on Atrium pages and API lists: N+1 queries, and the organization page loads all 8 tabs' data on every view;
- per-row costs in CSV import, export, SCIM and organization sync;
- test and CI time going into repeated migrations and repeated static analysis.

The surveys also turned up two **correctness bugs** that get fixed first:
1. **Stale permission cache.** `Authorizer` is a singleton (`RosterServiceProvider.php:83`) that keeps the request-scoped `Permissions` it got at construction. Under Octane or queue workers, permission results stay cached for the life of the worker, so revoked permissions keep passing.
2. **Audit chain fork.** `AuditLog::append()` locks the newest audit row (`orderByDesc('id')->lockForUpdate()`). On Postgres, a writer waiting on that lock gets the *old* last row back once the lock is released, so two entries share a `previous_hash` and the chain forks. With an empty table there is nothing to lock at all.

Outcome: per-request and per-row query counts become bounded and are guarded by query-budget tests, and the test suite and CI get faster.

## Approach and order

Each step ships with a **query-budget test** (`DB::listen` counter, see "Guardrails") so the gain is measured and can't quietly regress. A spec folder `agent-os/specs/2026-10-02-HHMM-speed-pass/` logs the decisions, as with the other features.

### 1. Correctness first
- **`Authorizer`:** bind it as `scoped`, not `singleton`, so it gets each request's `Permissions`.
- **Audit chain:**
  - Add a single-row `roster_audit_chain` table (`id`, `head_id`, `head_hash`), seeded by the audit migration (edited in place; the package is unreleased).
  - `append()` locks that row `FOR UPDATE`, chains from `head_hash`, inserts, then updates the head.
  - This works the same on MySQL, Postgres and SQLite, and with an empty log. Writes stay serialized, which the hash chain requires, but the lock is now one known row.
  - Add a test that simulates two interleaved appends.

### 2. Per-request runtime
- **`Permissions::contextScope()`:** memoize per user, and resolve it only when the cache misses. Check `$resolved` before resolving the scope.
- **`Roster`** (currently a singleton with no memo):
  - make it `scoped`, and memoize `organization($user)` / `team($user)`;
  - `team()` reuses the memoized organization;
  - a `Team` scope eager-loads its `organization`.
- **Invalidation:** a listener on `ActionFinishedEvent` calls `Permissions::flush()` and `Roster::forget()`. Any write Action in the same request (switching context, joining, assigning a role) then sees fresh state.
- **`Users::profileIfExists()`:** `setRelation('rosterProfile', $profile)`, including `null`, so `status()`, `rosterStatus()` and current-context lookups query once per user per request.
- **Indexes** (migrations edited in place):

  | Index | Used by |
  |---|---|
  | `roster_audit_entries.created_at` | prune, `since`/`until` filters |
  | `roster_transfers.impex_run_id` | `RunFailed` listener |
  | `roster_transfers.created_at` | `roster:prune-transfers` |
  | `roster_role_assignments (user_id, organization_id, team_id)` | `Permissions::for`, super-role check |
  | `roster_team_members.membership_id`, `roster_role_assignments.organization_id` / `team_id` | joins and cascades (Postgres doesn't index foreign keys) |
  | `roster_memberships (user_id, created_at)` | current-organization fallback ordering |

### 3. Pages and API lists
- **`OrganizationUiController::show`:**
  - Load only the active tab's data with a `match ($tab)`. The invitations tab also needs teams, and the SCIM tab needs SSO connections.
  - Replace `per_page => 100` with real pagination plus `<x-atrium::pagination>`. Today lists are silently cut at 100.
  - Let `ResolvesScopes::organizationFrom()` accept an `Organization` instance, so the actions stop re-querying the organization by slug.
- **`UserUiController::show`:**
  - Drop the unused `organizations` list and the `organization.teams` eager load.
  - Pass the user model, not its key, to the list actions; they currently re-run `findOrFail` 3 times.
  - Resolve the user's current organization once.
- **`UserResource` (`current_organization` / `current_team`):** add `Roster::preload($users)`, which batch-loads the memberships and teams for a page (2 queries) into the memo. `ListUsersAction` calls it, so the list costs a fixed number of queries whatever its length.
- **`TransferResource`:**
  - `ListTransfersAction` preloads Impex batches with one `whereIn(run_id)`.
  - List rows judge `downloadable` from status plus `output_path`, with no disk or S3 `exists()` call per row. The disk check stays on show and download.
- **`InvitationResource`:** `ListInvitationsAction` preloads the teams it references in one query.
- **`OrganizationResource`:** eager-load `owner` in the list and show actions, and wrap `links` in `whenLoaded`. The Atrium organization index stops loading `domains`.
- **Atrium widgets:** fold the three organization-widget counts into one query.

### 4. Bulk paths
- **CSV planners:**
  - bind them `scoped`, so one instance serves a whole job or batch chunk;
  - memoize per transfer: the organization and its domains, teams keyed by slug and name, roles with their permission names, the actor's permission set, and each team's member user IDs (TeamsPlanner);
  - the per-row email lookup stays, through a new single `Users::findByEmail()`.
- **`Users::findByEmail()`:** one case-insensitive query, `whereRaw('lower(col) = ?')`. It replaces the four `whereLike(...)->get()->first(strcasecmp)` copies, which pulled every partial match into PHP. Apps on Postgres with large user tables can add a functional index on `lower(email)`; the README will say so.
- **The transfer report:**
  - Move per-row results into a new `roster_transfer_rows` table (`transfer_id`, `line`, `values` JSON, `action`, `reasons`, `result`, `result_reasons`).
  - `report` keeps only the summary, results and error.
  - `ApplyImportRow` and `CsvRowsSource` select the transfer without large columns, and `FinishImport` updates rows in bulk rather than rewriting the JSON.
  - The transfer page, `show-transfer-tool` and `GET transfers/{id}` paginate rows (`rows_page`). Tests are adjusted.
- **`Reader`:** read the header once per instance, and let `ValidateImport` count rows while planning instead of reading the file twice.
- **`MembersExporter`:** `with(['user.rosterProfile', 'teams'])`, plus one role-assignment query per chunk (`whereIn user_id`, `with role`).
- **`SyncOrganizationAction`:**
  - `differs()` uses the eager-loaded `domains`;
  - when the outcome is `unchanged`, skip the post-write `refresh/load/loadCount`;
  - `SyncOrganizationsAction` prefetches every link for the batch with one `whereIn`.
- **SCIM:**
  - eager-load `user.rosterProfile`;
  - batch each page's memberships, team groups and ScimGroup lookups, so `ScimMapper` runs no queries per resource;
  - make the `eq` filter one organization-scoped `lower(col) = ?` query, replacing the separate LIKE prefilter.
- **Audit prune:** delete in batches by id (e.g. 1000), using the new `created_at` index.

### 5. Tests and CI
- **CI** (`.github/workflows/tests.yml`):
  - a separate `static` job runs PHPStan, Pint and type coverage once (Ubuntu, PHP 8.4);
  - the 8-job matrix then runs only the test suite;
  - add a composer cache (`actions/cache` on `composer config cache-files-dir`) to both workflows, and an npm cache to `browser-tests.yml`.
- **Test database** (measure first, adopt only if it pays):
  - **Today:** every test re-runs 19 migrations.
  - **Prototype:** a file-backed SQLite database per test-case class and per parallel worker (path built from the class name and `ParallelTesting::token()`), migrated once and wrapped in a transaction per test (`RefreshDatabase` semantics).
  - **Watch out for:** the mode test cases (`Ulid`, `Traitless`, `Owned`, `AuditOff`, `SsoUnavailable`, `TransfersUnavailable`) use different schemas, so each needs its own file and its own "migrated" flag. Laravel's static `RefreshDatabaseState::$migrated` is global, so it needs a per-class guard.
  - **Adopt only if** the full suite is at least 30% faster and every mode passes. Otherwise record the measurement in the spec and leave things as they are.

## Guardrails: query budgets

A `tests/Feature/PerformanceTest.php` with a helper `queries(fn () => ...)` (counts via `DB::listen`) asserts upper bounds, mostly as "doesn't grow with N":
- 20 `$user->can('roster.*')` calls with no scope cost no more than the first one plus 0.
- The users API list costs the same at 5 rows and at 50.
- The organization page's members tab stays at or below a fixed budget, and so do its other tabs.
- The transfers list costs the same at 2 rows and at 20, and never calls the disk.
- A members import costs a bounded number of queries per row (asserted at 10 rows and at 50); so do the members export and a SCIM users page.
- Two interleaved audit appends produce one linear chain, and `roster:verify-audit` passes.

## Critical files

- **Container and listeners:** `src/RosterServiceProvider.php` (bindings, `ActionFinishedEvent` flush listener).
- **Runtime:** `src/Access/Permissions.php`, `src/Roster.php`, `src/Support/Users.php`.
- **Audit:** `src/Audit/AuditLog.php`; migrations 000002–000004 and 000008 (indexes, chain table, transfer rows table).
- **UI:** `src/Http/Ui/OrganizationUiController.php`, `UserUiController.php`, `resources/views/ui/organizations/show.blade.php`.
- **Lists and resources:** `src/Actions/ListUsersAction.php`, `ListTransfersAction.php`, `ListInvitationsAction.php`, `ListOrganizationsAction.php`, `ShowOrganizationAction.php`, `Concerns/ResolvesScopes.php`, and the matching resources.
- **Transfers:** `src/Transfers/Planners/*`, `Transfers.php`, `Csv/Reader.php`, `Flows/Actions/{ValidateImport,ApplyImportRow,FinishImport}.php`, `Flows/CsvRowsSource.php`, `Exporters/MembersExporter.php`, `Models/Transfer.php`, plus a new `Models/TransferRow.php`.
- **Sync, SCIM, CI:** `src/Actions/SyncOrganization(s)Action.php`, `src/Scim/{ScimMapper,ScimUsers,ScimGroups,ScimQuery}.php`, `.github/workflows/*.yml`, `tests/TestCase.php`.

## Verification

- `composer test` and `composer test:browser` are green, and the new `PerformanceTest` budgets pass.
- **Before and after**, recorded in the spec:
  - query counts for the organization members tab, the user page, a 50-row users API page, a 1,000-row members import (plan and apply) and the members export;
  - wall time of `composer test:unit`;
  - CI duration for the `tests` workflow.
- **Workbench:** click through the organization tabs (pagination appears past 25), import 1,000 members (the preview paginates), export members, then run `roster:verify-audit`.
- **Octane-style check:** a test runs two requests in the same app instance and asserts that a revoked role stops passing on the second request.
