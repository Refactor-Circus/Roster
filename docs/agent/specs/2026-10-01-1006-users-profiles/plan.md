# Roster — Feature 1: Users + Profiles

## Context

Roster is a new Refactor Circus package for headless, Action-first user management (`agent-os/product/`). `src/` is still the Testbench skeleton. The user asked to work through the roadmap in order, shaping each feature with `/shape-spec` so decisions get logged. This plan covers **Phase 1, feature 1: Users + profiles**, the first slice. After it ships, orgs/teams/members gets shaped next, then roles & permissions, then surface hardening.

Siblings (Impex, Cortex) set the patterns. Each operation is a plain `final` Action with `static rules()`. Thin HTTP FormRequest and MCP Request wrappers call `persist()`. JSON Resources use a `data` envelope. The Atrium plugin is a set of hand-written routes and controllers that call the Actions. Atrium has no user, role or screen layer, so Roster supplies the user-management screens.

## Decisions (from shaping)

- **User model is flexible, with three modes.** The model class comes from `config('roster.users.model')`.
  1. **Host model + `HasRoster` trait** (default): typed helpers (`profile()`, `rosterStatus()`, `isActive()`).
  2. **Host model, no trait**: the provider registers the `rosterProfile` relation with `Model::resolveRelationUsing`, so Roster works untouched.
  3. **Roster-owned**: optional `RefactorCircus\Roster\Models\User` (Authenticatable + HasRoster) plus an opt-in publishable migration, tag `roster-users-migration`, for apps without a users table.
- **Profiles live in a 1:1 `roster_profiles` table.** Columns: ULID id; `user_id` typed by `config('roster.users.key_type')` = `int|ulid|uuid`, unique; `display_name`, `avatar_url`, `timezone`, `locale`, `bio`, `meta` JSON; `status`, `status_reason`, `status_changed_at`. The profile is created lazily with `firstOrCreate`, and a missing profile means active.
- **Status:** `UserStatus` enum `Active | Suspended | Deactivated`. Suspend, Deactivate and Reactivate Actions. A `roster.active` middleware (`EnsureUserIsActive`) rejects non-active users with 403.
- **User column mapping:** `config('roster.users.columns')` (`name`, `email`, `password`) so Create/Update work on arbitrary host schemas.
- **Users are referenced by route key** (`getRouteKeyName()`), not by slug. Host users have no slugs. This is a deliberate deviation from the Cortex `slug-references` standard.
- **No authorization until feature 3.** Request `authorize()` returns true, and route middleware is the only gate. To fail closed meanwhile, HTTP routes (`roster.routes.enabled`) and MCP (`roster.mcp.web/local.enabled`) default to **off**. Atrium stays behind Atrium's own `viewAtrium` gate.
- **Surfaces ship per feature.** Every Action is reachable from HTTP, MCP and the Atrium dashboard, and an arch parity test covers all three (stricter than Impex's MCP-only check). Feature 4 becomes hardening: Cortex hookup, docs, any gaps.
- **Stack and conventions:** siblings' (PHP ^8.4, Laravel ^13.15, laravel/mcp, refactor-circus/atrium). The generic Cortex standards are adopted.
- **Events:** each Action dispatches `*ingActionEvent` / `*edActionEvent`. Writes are wrapped in `DB::transaction`. Business-rule failures throw `ValidationException::withMessages` (e.g. "cannot suspend yourself", "already suspended").

## Task 1: Save spec documentation + adopt standards

- Create `agent-os/specs/2026-10-01-1006-users-profiles/` containing `plan.md` (this plan), `shape.md` (scope, the decisions above, context), `standards.md` (full text of the applied standards) and `references.md` (Impex/Cortex/Atrium pointers below).
- Copy the generic Cortex standards into `agent-os/standards/`, renaming `cortex`→`roster` in examples, and fill in `index.yml`:
  - **backend:** actions, action-transactions, config-docs, container-bindings, domain-guards, feature-toggles, http-requests, http-resources, mcp-model-binding, mcp-responses, mcp-schemas, mcp-tools, publish-tags, routes, runtime-exceptions
  - **database:** migrations, models
  - **testing:** arch-tests, mcp-http-parity, test-layers, testcase-environment
- Add a roster-specific note to `slug-references`: users are referenced by route key.

## Task 2: Clean skeleton + config

- Remove the placeholders: `database/migrations/2026_01_01_000000_create_roster_placeholder_table.php`, `resources/views/placeholder.blade.php`, `src/Console/Commands/RosterCommand.php` and its registration, and the example tests.
- `config/roster.php` sections, documented per `config-docs`:
  - `users` (model, key_type, table, columns)
  - `routes` (enabled=false, prefix=`roster`, middleware=`['api']`)
  - `mcp` (web/local enabled=false, handle)
  - `ui` (enabled=true)
- Composer `extra.atrium.plugins` → `RefactorCircus\Roster\Atrium\RosterPlugin`.

## Task 3: Domain — model, enum, migrations, trait

- `src/Enums/UserStatus.php`.
- `src/Models/Profile.php`: final, HasUlids, `roster_profiles`, a `user()` BelongsTo pointing at the configured model, and a factory in `database/factories/ProfileFactory.php`.
- `src/Concerns/HasRoster.php`: `profile()` HasOne, `rosterProfile()` (firstOrCreate), `rosterStatus()`, `isActive()`.
- `src/Models/User.php`, the optional owned-mode model.
- Migrations:
  - `2026_01_01_000001_create_roster_profile_tables.php` (FK column type from config).
  - `database/migrations/users/2026_01_01_000000_create_roster_users_table.php`, published only via `roster-users-migration`.
- In the provider, register a dynamic relation for trait-less models.

## Task 4: Actions (`src/Actions/`)

Actions: `ListUsersAction` (search on name/email, status filter, per_page), `ShowUserAction`, `CreateUserAction` (user + profile in one transaction), `UpdateUserAction`, `DeleteUserAction` (respects host SoftDeletes), `UpdateProfileAction`, `SuspendUserAction`, `DeactivateUserAction`, `ReactivateUserAction`.

Supporting pieces:
- `src/Support/Users.php` resolves the configured model, query and column map. Bind it as a singleton.
- Events go in `src/Events/Action/`.
- `src/Http/Middleware/EnsureUserIsActive.php`, aliased as `roster.active`.

## Task 5: HTTP API surface

- `routes/roster.php` has explicit routes named `roster.users.*`, with POST verbs for `/suspend`, `/deactivate` and `/reactivate`. It registers only when `roster.routes.enabled === true`.
- `src/Http/Request.php` is the abstract FormRequest with `persist()`. Add `UserRequest` (resolves the bound user), one request per Action, and one-line controllers in `src/Http/Controllers/`.
- `src/Http/Resources/UserResource.php` and `ProfileResource.php`: the configured columns, profile and status, ISO8601 dates.

## Task 6: MCP surface

- `src/Mcp/RosterServer.php` (Name/Version/Instructions attributes, `TOOLS` const).
- `src/Mcp/Request.php` copies the Impex base (persist, structuredCollection, catches not-found). Add a `UserMcpRequest` model-binding base and one Tool plus one Request per Action, with shared schema traits.
- Register on `Mcp::web` / `Mcp::local` behind strict `=== true` toggles.

## Task 7: Atrium plugin

- `src/Atrium/RosterPlugin.php`:
  - **Navigation:** "Users".
  - **Routes:** `atrium.roster.users.*`: index (table, search, status filter), create, show/edit (user and profile forms), and status buttons for suspend/deactivate/reactivate with a reason modal.
  - **Widget:** user counts by status.
  - **Search source:** users.
- `src/Http/Ui/UserUiController.php` validates with `Action::rules()` and calls the Actions.
- Views in `resources/views/atrium/users/*.blade.php` built on `x-atrium::layout`, `table`, `form/*`, `badge`, `modal` and `avatar`.

## Task 8: Tests (Pest 5, per `test-layers`)

- TestCase loads `McpServiceProvider` and `AtriumServiceProvider`, then `RosterServiceProvider`. Use the testing sqlite database with FKs on and the workbench User + trait.
- `tests/Feature/UserActionsTest.php` and `ProfileActionsTest.php` hold all business rules: CRUD, lazy profile, status transitions and guards, column mapping, soft deletes, events.
- `tests/Feature/UserModesTest.php`:
  - a trait-less model through the dynamic relation;
  - the owned-mode `Models\User` with the published migration;
  - a `key_type=ulid` TestCase subclass.
- `tests/Feature/Http/UsersHttpTest.php` covers status codes, 422 mapping, `route()` names, and the disabled-by-default toggle.
- `tests/Feature/Mcp/UserToolsTest.php` checks envelopes, errors, and HTTP parity via `assertStructuredContent`.
- `tests/Feature/Ui/RosterPluginTest.php` checks the plugin registered, nav, `Route::has('atrium.roster.users.index')`, and that pages render and submit.
- `tests/Feature/MiddlewareTest.php` covers `roster.active`.
- `tests/ArchTest.php`:
  - Actions are final with a static `rules()`.
  - A `parityGaps()` check asserts every `src/Actions/*Action.php` is referenced in `src/Http/Requests`, `src/Mcp/Requests` **and** `src/Http/Ui`.
  - Models are final.

## Task 9: Docs

- README: install, the three user modes, config, middleware, HTTP/MCP enablement and the no-auth warning, Atrium.
- CHANGELOG `Unreleased` entry.
- Regenerate the Boost skill with the `package-generate-skill` skill.
- Update the memory roadmap note.

## References

- Actions, events, transactions: `/Users/jay/Projects/packages/Impex/src/Actions/CancelRunAction.php`
- HTTP request base: `Cortex/src/Http/Request.php`, `Cortex/src/Http/Requests/*`, `Cortex/routes/cortex.php`
- MCP base/server/tools: `Impex/src/Mcp/{Request.php,ImpexServer.php,Tools/*}` and registration in `Impex/src/ImpexServiceProvider.php` (`registerMcpServer`)
- Parity arch test: `Impex/tests/ArchTest.php` and `parityGaps()` in `Impex/tests/Pest.php`
- Atrium plugin: `Impex/src/Atrium/ImpexPlugin.php`, `Impex/src/Http/Ui/RunUiController.php`, `Atrium/src/Plugins/Plugin.php`, `Atrium/resources/views/components/`
- Plugin tests: `Impex/tests/Feature/Ui/ImpexPluginTest.php`

## Verification

- Use the PATH shim for Herd php84 (shell `php` is 8.3), then run `composer test`: PHPStan, Pint, type coverage 100% and Pest all green.
- Parity arch test fails if any Action is missing a surface. Sanity-check it by temporarily deleting one MCP request locally.
- `composer serve` on the workbench: open `/atrium` and walk through the Users screens (create, edit profile, suspend with reason, reactivate). Enable `roster.routes.enabled` and curl `GET /roster/users`.

## After this slice

Re-run `/shape-spec` for **feature 2: orgs/teams/members**. Each later feature gets its own spec folder, so the decision log accumulates in `agent-os/specs/`.
