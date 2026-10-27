# Roster — Feature 4: Surface Hardening

## Context

Last Phase 1 roadmap item. Features 1–3 built users/profiles, organizations/teams/members and roles/permissions, each on the HTTP API, MCP and Atrium, with authorization. This slice hardens and rounds out those surfaces:
- **Cortex** joins as a fourth surface, so its agents can use Roster's tools.
- **The API** gets rate limiting and safe error shapes.
- **Atrium** gets real-browser smoke tests.
- **The workbench** gets a seeded demo, so `composer serve` shows everything working.

Also confirmed during shaping: super-admins keep passing **every** Gate check (feature 3 behaviour unchanged).

Reference: Impex's Cortex integration, `../Impex/src/Cortex/CortexIntegration.php`, `../Impex/src/Mcp/Tool.php`, `ImpexServer::createContext()`, `../Impex/tests/CortexTestCase.php`, `../Impex/tests/Cortex/CortexTest.php`.

## Decisions (from shaping)

- **Cortex integration mirrors Impex.**
  - It is optional and active only when Cortex's provider is loaded and `roster.cortex.enabled === true` (default true).
  - It registers `RosterServer` with Cortex's `McpServerRegistry` under `roster.cortex.server` (default `roster`), and every `RosterServer::TOOLS` tool in `ToolRegistry`, tagged with the server name.
  - `roster.cortex.tools` (null or a list of names) limits which tools are offered.
  - Published Cortex overrides replace the server instructions (`createContext`) and tool descriptions, through a new `RefactorCircus\Roster\Mcp\Tool` base class that all 49 tools extend.
- **Agents act as the authenticated user.** Roster's authorization applies unchanged, so an agent run with no signed-in user is denied ("Unauthorized."). This is documented.
- **API hardening:**
  - A named rate limiter, `roster`, keyed by user id or IP. It is configurable (`roster.rate_limit.per_minute`, default 120; null disables) and included in the default `roster.routes.middleware` and `roster.mcp.web.middleware` as `throttle:roster`.
  - Not-found inside Roster's API and MCP returns a generic `{"message": "Not found."}` (HTTP 404), never Laravel's `No query results for model [RefactorCircus\Roster\...]`, which leaks class names.
  - A docs test checks that every `roster.*` API route name appears in the README route table.
- **Browser tests:**
  - `pestphp/pest-plugin-browser` with Playwright, following Atrium's `BrowserTestCase` (it copies Atrium's public assets).
  - A separate `Browser` testsuite run by `composer test:browser` and kept out of `composer test`. CI gets a dedicated job.
  - Smoke flows: the users list/create/suspend, the org create → team → invite, and the role create → assign.
- **Workbench demo:**
  - `WorkbenchServiceProvider` enables the API, local MCP and Atrium, and points `roster.users.model` at the workbench User.
  - The seeder creates a super-admin `admin@example.com` / `password`, organizations Acme and Globex with teams and members, a pending invitation, a custom `invoices.edit` permission and a "Billing" role.
  - `testbench.yaml` logs in as the admin and starts at `/atrium`.

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-surface-hardening/` with `plan.md`, `shape.md`, `standards.md` (feature 3's set) and `references.md` (the Impex Cortex files above and Atrium's `tests/BrowserTestCase.php`).

## Task 2: Cortex integration

- `composer.json`:
  - `require-dev` `refactor-circus/cortex: dev-main`
  - a `suggest` entry
  - a vcs repository `https://github.com/Refactor-Circus/cortex.git`
  - then `composer update`
- `src/Cortex/CortexIntegration.php`: a copy of the Impex class, adapted (`active`, `register`, `serverName`, `tools`, `instructions`, `description`).
- `src/Mcp/Tool.php`: an abstract base overriding `description()` with the Cortex override. All `src/Mcp/Tools/*Tool.php` switch from `Laravel\Mcp\Server\Tool` to it (mechanical).
- `RosterServer::createContext()` applies instruction overrides.
- The provider calls `CortexIntegration::register()` first in `boot()`.
- Config section `cortex` (`enabled`, `server`, `tools`), with a banner noting that agents act as the signed-in user.
- Tests:
  - `tests/CortexTestCase.php`: Ai and Cortex providers, Cortex migrations, `cortex.cache.store=array`.
  - `tests/Cortex/CortexTest.php`: server registered, all tools registered and tagged, `tools` filter, instruction and description overrides, an agent call as a super-admin succeeds and as a guest is denied, disabled → inactive.
  - A `Cortex` phpunit testsuite.

## Task 3: API hardening

- **Provider:** `RateLimiter::for('roster', ...)` using `Limit::perMinute(config)->by(user id ?: ip)`, or `Limit::none()` when null. Default middleware becomes `['api', 'throttle:roster']` for routes and MCP web, with config banners updated.
- **Not-found mapping:**
  - The provider registers a renderable on the exception handler. A `ModelNotFoundException` or `NotFoundHttpException` whose previous exception is a `ModelNotFoundException`, on a request whose route name starts with `roster.`, returns `response()->json(['message' => __('roster::roster.not_found')], 404)`.
  - MCP already returns "Not found.".
- **Tests:**
  - `tests/Feature/Http/HardeningTest.php`: 429 after the limit (limit set low per test), a disabled limit, 404 bodies leaking no class name across a sample of routes, and 422 shape consistency.
  - `tests/Feature/DocsTest.php`: every API route name is in the README table, and every MCP tool name is in the README tool list. The README gets a short MCP tool list.

## Task 4: Browser tests

- Dev deps: `pestphp/pest-plugin-browser ^5.0`. `package.json` with `playwright` (postinstall `playwright install chromium`), as Atrium has.
- `tests/BrowserTestCase.php`: extends `AuthorizationTestCase`, copies Atrium's `vendor/refactor-circus/atrium/public` assets into `public_path('vendor/atrium')`, and logs in as a seeded super-admin.
- `tests/Browser/*`:
  - `UsersBrowserTest` (list, search, create, suspend → badge)
  - `OrganizationsBrowserTest` (create org, add team, send invitation)
  - `RolesBrowserTest` (create role with permissions, assign to a user)
- phpunit: a `Browser` testsuite. Composer: a `test:browser` script, with `test:unit` restricted to the non-browser suites. CI: a `browser` job in `.github/workflows/tests.yml` (ubuntu, node, `npx playwright install --with-deps chromium`).

## Task 5: Workbench demo

- `workbench/app/Providers/WorkbenchServiceProvider.php`: config overrides (users model, routes on, `mcp.local` on).
- `workbench/database/seeders/DatabaseSeeder.php`: runs the Roster Actions (not raw inserts) to build the demo data, and runs `roster:grant-super-admin`.
- `testbench.yaml`:
  - providers: Mcp, Atrium, Workbench
  - `migrations` include the package migrations
  - `workbench.start: /atrium`
  - `workbench.user: admin@example.com`
  - `discovers.web: true`
- README "Trying it locally" section: `composer serve`, the login, `php vendor/bin/testbench mcp:start roster`.

## Task 6: Docs

- README: a Cortex section, a rate-limiting note, the MCP tool list, local demo, and browser test instructions in a Testing section.
- CHANGELOG, Boost skill (Cortex and throttling notes), the spec's implementation decisions, and the memory note marking Phase 1 complete.

## Verification

- `composer test` (Herd php84 shim) is green, including the Cortex suite.
- `composer test:browser` is green locally after `npm install` (Playwright chromium).
- The parity and authorization matrices are still green.
- `composer serve`: you land on `/atrium` as the admin, and Users / Organizations / Roles / Permissions are populated. `curl` against the API returns 401 without a token. `testbench mcp:start roster` lists the tools.
