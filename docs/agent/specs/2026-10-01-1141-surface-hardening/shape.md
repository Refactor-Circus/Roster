# Surface Hardening — Shaping Notes

## Scope

Phase 1 MVP, feature 4 (last). Cortex becomes a fourth surface; the API gets rate limiting and safe error shapes; Atrium gets real-browser smoke tests; the workbench gets a seeded demo.

## Decisions

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


## Context

- **Visuals:** None.
- **References:** Impex's Cortex integration, Atrium's BrowserTestCase.
- **Product alignment:** roadmap Phase 1 item 4. Completes the MVP.
- **Confirmed:** super-admins keep passing every Gate check (feature 3 open item).

## Standards Applied

- Same set as feature 3. `feature-toggles` covers the Cortex `enabled` flag (strict `=== true`); `config-docs` covers the cortex and rate-limit banners.

## Decisions made during implementation

- **All 49 tools now extend `RefactorCircus\Roster\Mcp\Tool`**, Roster's own base class, which applies Cortex description overrides. Without it, MCP clients would see the code description even when agents saw the published one.
- **The Cortex test case extends `AuthorizationTestCase`**, so agent calls are proven to go through Roster's permissions (guest refused, user without the permission refused, super-admin allowed).
- **The not-found mapping hooks the handler with `callAfterResolving`** and applies only when it is Laravel's `Foundation\Exceptions\Handler`. Apps with a custom handler keep their own responses. It only touches routes named `roster.*`, and only when the 404 came from a `ModelNotFoundException`.
- **Permission routes resolve the record in `prepareForValidation`**, so a missing permission is a 404, not a 422.
- **The docs test** checks every `roster.*` API route name and every MCP tool name against the README.
- **Browser tests run under `AuthorizationTestCase`** as a real super-admin. They're excluded from `composer test` and `test:types`, and run in their own CI workflow (`.github/workflows/browser-tests.yml`, copied from Atrium).
- **The workbench registers the Atrium plugin via `atrium.plugins`**, because discovery reads `installed.json`, which never lists the root package. It also migrates Cortex's tables, since Cortex (a dev dependency) is auto-discovered there.
- **The workbench seeder uses Roster's Actions** (with `Notification::fake()` for the invitation), so the demo data obeys every guard.
