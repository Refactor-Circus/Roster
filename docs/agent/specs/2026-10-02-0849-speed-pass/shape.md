# Speed Pass — Shaping Notes

## Scope

The user asked where speed can be gained. Everything is in scope: per-request runtime, Atrium pages and API lists, bulk paths (CSV, SCIM, organization sync) and tests/CI. Two correctness bugs found along the way are fixed first.

## Decisions

- **Correctness first:**
  - `Authorizer` is bound per request (it held the first request's `Permissions` for the life of the worker).
  - Audit appends lock a single chain-head row (`roster_audit_chain`), not the newest entry. Locking the newest entry forks the chain on Postgres and locks nothing on an empty log.
- **Per-request memoization with invalidation:**
  - `Roster` (current organization and team) and `Permissions` are per request and memoized.
  - Any finished *write* Action flushes both. List and show events don't, because a page runs several of them.
  - `Users::profileIfExists()` caches the profile on the model, null included.
- **Guardrails:** query-budget tests (`tests/Feature/PerformanceTest.php`) assert counts that don't grow with N.
- **Test database:** migrate once per test-case class and worker into a file database, then run each test in a rolled-back transaction. Adopted only after measuring it and checking that every mode passes.

## Decisions made during implementation

- **Email lookups:**
  - The plan's `lower(email) = ?` lookup needed a raw SQL fragment, which the PHPStan security rules refuse. On a closer look, the existing case-insensitive LIKE has no leading wildcard, so it can use an index and was never a scan.
  - Kept that approach, now in one `Users::findByEmail()` instead of 9 copies (SSO login, invitations, SCIM, planners, super-admin command, `ssoRequiredFor`). No README note about functional indexes.
- **Planner caching:**
  - Impex applies each batch item as its own job, and Laravel clears scoped services before every job, so per-job memos don't survive between rows.
  - Import-stable data now lives in a worker-level `Transfers\PlanCache`, keyed by transfer: organization, domains, teams, roles with permissions, the importer and their permissions, and the invite permission. It holds the last 10 transfers and is cleared when an import finishes or closes.
  - It may be up to one import run stale, which is accepted for bulk jobs. The planners themselves are no longer bound specially.
- **Audit snapshots:** only the event's subject is snapshotted, and only when the event can change its own fields:
  - users on `user.*`, `profile.*` and `context.*` events;
  - other models when the noun matches their type.

  This removes the profile query that relationship events (`member.added`, `team_member.added`, `role.assigned`) paid for no diff, and stops non-subject snapshots piling up in memory during long imports. The full suite passing shows no recorded field changes were lost.
- **Transfer rows:** per-row results moved from the `report` JSON into `roster_transfer_rows`.
  - The preview bulk-inserts 500 rows at a time.
  - Each applied row updates its own result, so retries are idempotent.
  - `FinishImport` only marks rows that failed outright and counts results with a GROUP BY.
  - `report` keeps the summary, results and error.
  - Show (HTTP, MCP, Atrium) pages rows with `rows_page` (100 per page in the API, 50 in Atrium). `Transfer::rows()` remains as an "all rows" convenience.
  - `roster:prune-transfers` deletes the rows too, since they hold imported personal data.
- **The organization page loads only the active tab**, and every tab's list is paginated, 25 per page. Lists were previously cut off at 100 with no way to see more.
  - The invitations tab still loads all teams, as form options.
  - The roles tab caps roles at 100; it's a reference list.
  - `ResolvesScopes::organizationFrom()` accepts an `Organization` instance, and `Users::resolve()` accepts a user model, so callers stop re-querying what they already have.
- **The users list preloads context:** `Roster::preload()` resolves current organizations and teams for a page in two queries. A test checks that it gives the same answers as the per-user path for 4 edge cases.
- **Transfers list:**
  - progress is batch-loaded;
  - list rows judge "downloadable" from status plus `output_path`, with no disk or S3 call. Show and download still check the file.
- **Organization sync:** `execute(..., fresh: false)` skips the post-write reload for bulk callers, and `differs()` reads the domains relation once.
- **SCIM user lists** batch group membership per page (cached on `ScimContext`) and eager-load profiles.
- **Follow-up, SCIM:**
  - `eq` filters with no LIKE wildcard in the value now filter in place with a case-insensitive LIKE (an exact match), inside the organization-scoped query. Values containing `%` or `_` keep the prefilter plus exact-compare path. A test checks that `a_b@…` doesn't match `axb@…`.
  - The Groups list batches each page's members on `ScimContext` (2 queries per page), the same as the users' groups.
- **Follow-up, Atrium search:**
  - Measured at 10–20ms per request with sync, so the leading-wildcard LIKE wasn't worth changing.
  - Atrium `ec59e26` (concurrent sources, process driver by default, per-source caps, classification) needed Roster changes. Roster's single source returned users *and* organizations, so `results.per_source` (5) could crowd organizations out.
  - Atrium now lets `Plugin::search()` return several sources (Atrium `3b4a9ad`, backward compatible). Roster returns `roster-users` and `roster-organizations`, each with a description for classification, static closures that serialize into child processes, and a limit taken from `per_source`.
  - The workbench showed process-driver results linking to `APP_URL` (`http://localhost`) instead of the request's host. Fixed in Atrium `76f9454`: each task gets the request's root URL and scheme, applied only outside a web request.
  - The process driver costs about 240–370ms per search, against 10–20ms with sync. That's Atrium's default from `ec59e26`, left as is: it's the price of per-source timeouts.
- **Tests:**
  - Test hashing uses bcrypt cost 4. The workbench's `.env` (`BCRYPT_ROUNDS=12`, written by `composer build`) was leaking into test runs, and hashing new users dominated the import tests.
  - SQLite pragmas for the test file database: `synchronous=off`, journal in memory.
  - The parallel suite went from 17s to 6–9s, and the serial suite from 67s to 20s.
- **CI:**
  - A separate `static` job runs PHPStan, Pint and type coverage once.
  - The test matrix only runs tests.
  - Composer download caches were added to both workflows, plus npm and Playwright browser caches for the browser workflow.
  - `phpstan.windows.neon` was removed, since PHPStan no longer runs on Windows.

## Context

- **Visuals:** None.
- **References:** three read-only survey reports (runtime, pages and lists, bulk and CI) plus measurements; see `measurements.md`.
- **Product alignment:** a non-feature quality pass after the Phase 3 organization sync.
