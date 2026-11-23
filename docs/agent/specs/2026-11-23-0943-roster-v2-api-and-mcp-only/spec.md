# Roster v2: API and MCP only

## Goal

Roster v2.0.0 ships only its JSON API and MCP server. The Atrium dashboard moves into Atrium v2, whose Nuxt layer calls Roster through a committed OpenAPI document and a generated TypeScript client. This spec adds the API that the dashboard used to compute in PHP and removes every trace of Atrium from the package.

Waits for Keystone `2026-11-23-0943-drop-the-atrium-plugin-hook`; build against a Keystone `main` that includes it. Decisions: `decisions.md` (D-001 to D-017).

## Acceptance criteria

### New API

The new routes live in the existing API group: prefix `roster.routes.prefix` (default `roster`), names `roster.*`, registered only while `roster.routes.enabled` is `true`.

| Method | URI | Name |
| --- | --- | --- |
| GET | `{prefix}/organizations/{organization}/role-assignments` | `roster.organizations.role-assignments.index` |
| GET | `{prefix}/invitations/{token}` | `roster.invitations.lookup` (public, token only) |
| GET | `{prefix}/stats/users` | `roster.stats.users` |
| GET | `{prefix}/stats/organizations` | `roster.stats.organizations` |
| GET | `{prefix}/search/users?q=&limit=` | `roster.search.users` |
| GET | `{prefix}/search/organizations?q=&limit=` | `roster.search.organizations` |

1. `GET organizations/{organization}/role-assignments` answers a paginated `RoleAssignmentResource` collection of every role assignment in the organization and its teams, built by `ListRoleAssignmentsAction` with `ListRoleAssignmentsData(organization: …)`. It pages like `ListRoleAssignmentsData::pagination()`, and each item equals the item that `GET users/{user}/roles` returns for the same assignment. It needs `roster.roles.view` in that organization. An unknown or deleted organization answers 404.
2. `GET invitations/{token}` works without authentication. For a pending, unexpired invitation whose organization is not deleted, it answers 200 with exactly `{"data": {"email", "organization": {"name"}, "teams": [<team names, sorted>], "expires_at": <ISO-8601>}}`.
3. **Security: the token cannot be enumerated.** An unknown token, an expired, revoked, accepted or declined invitation, or one whose organization is deleted, gets the same 404 status and body. The route only matches tokens of 48 alphanumeric characters, so a malformed token gets 404 before any query runs. Lookup goes through `InvitationTokens::hash()` and never compares plain tokens.
4. **Security: the lookup is public and rate-limited.** It is registered with `roster.invitations.lookup_middleware` (default `['api', 'throttle:roster-invitation-lookup']`) and never with `roster.routes.middleware`. A test proves a guest still gets 200 while `roster.routes.middleware` contains an authentication middleware. The `roster-invitation-lookup` limiter allows `roster.rate_limit.invitation_lookups_per_minute` (default 10) requests per IP and answers 429 after that, whether the tokens are valid or not. Like the rest of the API, the route is absent while `roster.routes.enabled` is false.
5. **Security: the payload stays minimal.** The response carries `Cache-Control: no-store`. It never contains the token, `token_hash`, an id or slug, the inviter, the status or any timestamp other than `expires_at`. The plain token is in neither event that `LookUpInvitationAction` dispatches: a test serializes both events and searches them for it.
6. A user's status has no new endpoint. The existing `users.suspend`, `users.deactivate` and `users.reactivate` routes cover the old status form, and the README's HTTP API section maps each target status to its route (`DocsTest`).
7. `GET stats/users` answers `{"data": {"total": int, "by_status": {<every status in Statuses::users()>: int}}}`. A user without a profile row counts as active. Counts are cached for 60 seconds under `Users::STATUS_COUNTS_CACHE`, which is renamed to `roster:user-status-counts` and is still forgotten whenever a User action finishes. The endpoint needs global `roster.users.view`.
8. `GET stats/organizations` answers `{"data": {"organizations": int, "teams": int, "pending_invitations": int}}`, counting records that are not deleted and invitations in the `pending()` scope. It needs global `roster.organizations.view`.
9. `GET search/users` answers a `UserSummaryResource` collection of the users whose configured name or email column starts with `q`. `q` is required (1–100 characters). `limit` is 1–25 and defaults to 5. `%`, `_` and `\` in `q` match literally. The endpoint needs global `roster.users.view`.
10. `GET search/organizations` answers an `OrganizationResource` collection of the non-deleted organizations whose name or slug starts with `q`, ordered by name. It uses the same `q`, `limit` and literal-matching rules. It is `acrossOrganizations()`: a caller who holds `roster.organizations.view` only in some organizations gets only those.
11. Each new endpoint is a `{Verb}{Entity}Request` whose `persist()` calls one action. That action takes an `{Action}Data` when it has input and dispatches a starting and a finished event. Each new payload is a final resource that declares `schema()`. `tests/ArchTest.php` stays green, including `actionEventGaps()`, `writesOutsideTransactions()` and the request rules.
12. MCP: the new `show-user-stats-tool` and `show-organization-stats-tool` declare output schemas, and their structured content equals the matching HTTP payload. `parityGaps()` is empty. `PARITY_EXCEPTIONS` lists `LookUpInvitationAction`, `SearchUsersAction` and `SearchOrganizationsAction` under `mcp`, each with its reason.
13. `tests/Authorization` covers 401 for a guest and 403 for a user without the permission on every new authenticated endpoint, and the organization-limited result of `search/organizations`.

### Atrium removal

14. `src/Atrium` is deleted. `tests/ArchTest.php` fails if any file under `src/`, `config/`, `database/`, `lang/`, `resources/views/` or `workbench/`, or `testbench.yaml`, contains `atrium` (case-insensitive). It also fails if `composer.json`'s `require`, `require-dev`, `suggest`, `conflict` or `extra` holds a `refactor-circus/atrium` key or an `atrium` key.
15. No route named `atrium.roster.*` is registered, and `RosterServiceProvider` registers no Atrium provider. The `rosterCan` Blade directive, the `rosterUsesAtrium` view share and `RosterServiceProvider::$withoutAtrium` are gone.
16. `resources/views` holds only `invitations/show`, `impersonation-banner` and `partials/impersonation-banner-plain`, so `ui/` is gone. `roster::impersonation-banner` renders the plain banner. The signed `roster.invitations.show` page still renders through `Domains\Invitation\Contracts\InvitationPage`, which is bound to `PlainInvitationPage` by default, and a host's own binding is used instead when one is set. `roster.invitations.accept_url` still overrides the link in the email.
17. `config/roster.php` has no `atrium` section and no `RosterSupportFeature`, and no banner mentions Atrium. `roster.routes.enabled` stays `false`, and its banner says that the public invitation lookup follows it.
18. Roster defines no `viewAtrium` gate and no `atrium.view` built-in permission, and `'atrium.'` is gone from `Permissions::GLOBAL_ONLY_PREFIXES`. Stored `atrium.view` rows and grants are left alone: `roster:sync-permissions` reports them as unregistered and keeps them.
19. `UserAwaitingApprovalNotification` links to `roster.users.approvals.review_url` when it is set, with `{user}` replaced by the route key; otherwise the email has no button. Leaving impersonation returns to `roster.impersonation.return_to`, or to `/` when that is not set.
20. The Atrium-only tests are deleted: `tests/Feature/Ui`, `tests/Browser`, `BrowserTestCase`, `Modes/AtriumFeatures`, `Modes/AtriumUnavailable` and `Modes/PennantPlus` with their TestCases, `Feature/AtriumSeamTest`, `Feature/NavigationSectionTest`, and `Authorization/AtriumAuthorizationTest`, `ScreenControlsTest` and `ScreenRegistryResetTest`. So is the browser tooling: the `Browser` testsuite, the `test:browser` script, `--exclude-testsuite=Browser`, `pestphp/pest-plugin-browser`, the root `package.json` and `package-lock.json` (Playwright), `.github/workflows/browser-tests.yml`, the `without-atrium` CI job and `browser_test` in `ring-master.yml`. `refactor-circus/pennantplus` leaves `require-dev` and `suggest`. `composer test` passes.

### OpenAPI and TypeScript client

21. `dedoc/scramble` is in `require-dev` only. The workbench configures it: the API path is Roster's prefix, and the routes are filtered to the API group plus the public lookup. The committed `openapi.json` (OpenAPI 3.1, `info.title` "Roster API", `info.version` "2.0.0") has one operation per registered `roster.*` API route, including the six new ones and `roster.history.index`, with `operationId` equal to the route name. It has none for a web route (`roster.invitations.show`, `roster.invitations.page.*`, `roster.impersonation.*`, `roster.transfers.file`). A test compares the route list to the operations.
22. The committed `client/` is an npm package. `package.json` has the name `@refactor-circus/roster-client` and version `2.0.0`, with `openapi-fetch` in `dependencies`, `openapi-typescript` and `typescript` in `devDependencies`, and `generate` and `typecheck` scripts. `client/src/schema.d.ts` is generated from `../openapi.json`. `client/src/index.ts` exports `createRosterClient(options)`, which wraps `openapi-fetch`'s `createClient<paths>()`, and re-exports the `paths` and `components` types.
23. `composer client:generate` exports `openapi.json` from the Testbench workbench with Scramble, then runs `npm --prefix client run generate`.
24. A Pest test fails when a fresh Scramble export from the workbench differs from the committed `openapi.json`. It also fails when the sha256 of `openapi.json` recorded in `client/src/schema.d.ts`'s header differs from the committed file's. A CI `client` job runs `npm ci`, `generate` and `typecheck` in `client/` and fails on any diff to `client/`.

### Docs and standards

25. The README describes Roster as a JSON API + MCP package and names Atrium v2 as its optional UI. The "Atrium dashboard" and "Screen extensions" sections are gone. The README documents the six new endpoints, the status mapping (AC 6), the lookup's security model, the default `roster.routes.enabled = false`, and how to regenerate and consume the OpenAPI document and the client. `DocsTest` is updated to match.
26. `CHANGELOG.md` has a v2.0.0 entry. Under Breaking, its upgrade note lists the removed classes, `atrium.roster.*` routes, views, `roster.atrium` config, `@rosterCan`, the `viewAtrium` gate and `atrium.view` permission, `RosterSupportFeature` and the pennantplus suggestion, and points to Atrium v2. Under Added, it lists the new endpoints, MCP tools, config keys, `openapi.json` and the client.
27. The Boost skill (`resources/boost/skills/roster-development/SKILL.md`), `CLAUDE.md` and `AGENTS.md` have no Atrium architecture text, and they describe the new endpoints and the regeneration workflow. The local standards are updated: `roster/atrium-views` is deleted; `roster/action-surfaces` names HTTP + MCP; `roster/optional-surfaces` loses the dashboard row; `roster/testbench-environment` updates the provider list and modes; and `http/authorization` drops its Atrium, `ScreenAccess` and `@rosterCan` text. `docs/agent/standards-feedback.md` has a row for each deviation listed under Design.

## Design

### New endpoints, by domain

- **Role**: `IndexOrganizationRoleAssignmentsRequest extends OrganizationRequest` sets `ability()` to `roster.roles.view`, and the base supplies `scope()` (the organization). It persists through `ListRoleAssignmentsAction` with `organization` = the slug and answers `RoleAssignmentResource::collection(...)`. `OrganizationRoleAssignmentController::index` is wired in `Role/routes.php`. No new MCP tool (D-011).
- **Invitation**:
  - Route: `src/Domains/Invitation/public.php` holds the lookup route. `InvitationServiceProvider::boot()` loads it itself only when `roster.routes.enabled === true` and routes are not cached, with prefix `roster.routes.prefix`, name prefix `roster.` and the middleware from `roster.invitations.lookup_middleware`, plus `->where('token', '[A-Za-z0-9]{48}')`.
  - Action: `LookUpInvitationAction::execute(LookUpInvitationData $data): InvitationModel` finds the invitation by hash. It throws `InvalidInvitationException::forToken()` unless the invitation is pending, unexpired and in a live organization, then loads `organization`.
  - Events: `InvitationLookingUpActionEvent` (no payload) and `InvitationLookedUpActionEvent` (the invitation). Neither carries the Data, because it holds the token. This is a deviation, logged in standards-feedback.
  - Request: `LookUpInvitationRequest extends Http\Request` overrides `authorize()` to return `true`, because the token is the credential, as `InvitationResponseRequest` already does. This is a deviation, logged in standards-feedback. `persist()` catches `InvalidInvitationException` and aborts 404 with a fixed message, and it adds `Cache-Control: no-store`.
  - Resource: `InvitationLookupResource` is final, with `email`, `organization.name`, `teams` (names) and `expires_at`.
  - Rate limiting: `RosterServiceProvider::registerRateLimiter()` adds `roster-invitation-lookup`, by IP, from `roster.rate_limit.invitation_lookups_per_minute`. As with the main limiter, null turns it off.
- **User**:
  - Stats: `ShowUserStatsAction` (no input) returns the cached counts that `RosterPlugin::statusCounts()` computed before, moved here verbatim. It is answered through `UserStatsResource` (`src/Domains/User/Http/Resources`), from `ShowUserStatsRequest` (global `roster.users.view`).
  - Search: `SearchUsersAction` takes `SearchUsersData(q, limit)` and returns users through `UserSummaryResource`, from `SearchUsersRequest` (global `roster.users.view`). It uses `Users::query()` and `Users::column('name'|'email')`, with `LIKE` wildcards escaped.
  - Wiring: `UserDirectoryController` (`stats`, `search`) in `User/routes.php`, and `ShowUserStatsMcpRequest` with `ShowUserStatsTool` listed in `RosterServer`.
- **Organization**:
  - Stats: `ShowOrganizationStatsAction` runs the single three-subquery count that the organizations widget used. It is answered through `OrganizationStatsResource`, from `ShowOrganizationStatsRequest` (global `roster.organizations.view`).
  - Search: `SearchOrganizationsAction` takes `SearchOrganizationsData` and the `$within` organizations, and is answered through `OrganizationResource`. `SearchOrganizationsRequest` uses `acrossOrganizations()`.
  - Wiring: `OrganizationDirectoryController` in `Organization/routes.php`, and `ShowOrganizationStatsMcpRequest` with `ShowOrganizationStatsTool`.
- **Shared**: event names follow `{Entity}{Verb-ing|Verb-ed}ActionEvent`: `UserStatsShowingActionEvent`/`UserStatsShownActionEvent`, `UsersSearchingActionEvent`/`UsersSearchedActionEvent` and the Organization equivalents. Reads have no `DB::transaction()`, like the existing list and show actions.

### Removal

- **Code**:
  - Delete `src/Atrium/**`. In `RosterServiceProvider`, remove the seam registration, `$withoutAtrium`, the import and the docblocks.
  - Strip the Atrium comments from `InvitationServiceProvider`, `InvitationPage`, `PlainInvitationPage` (drop `@internal` "shared with Atrium" and keep `data()`), `Contracts\Status`, `Support\Users`, `Support\Policies\Policy`, `Authorizer` and `PurgeOrganizationAction`.
  - `PermissionServiceProvider` loses the `viewAtrium` gate, `BuiltInRoles` loses `atrium.view`, and `Permissions::GLOBAL_ONLY_PREFIXES` loses `'atrium.'`.
  - `UserAwaitingApprovalNotification` uses the new `roster.users.approvals.review_url` (default `null`). `ImpersonationWebController::returnTo()` drops the Atrium route fallback.
- **Views**:
  - Delete `resources/views/ui/**`.
  - `impersonation-banner.blade.php` includes `partials/impersonation-banner-plain` only.
- **Config**:
  - Remove the `atrium` section.
  - Add `invitations.lookup_middleware`, `rate_limit.invitation_lookups_per_minute` and `users.approvals.review_url`, each with a banner.
  - Rewrite the banners that mention Atrium.
- **Composer**:
  - Drop `refactor-circus/atrium` and `refactor-circus/pennantplus` from `require-dev`, `suggest` and `conflict`.
  - Drop `extra.atrium` and `pestphp/pest-plugin-browser`.
  - Keep the Atrium VCS `repositories` entry only if `composer update` needs it for a transitive dependency (D-016).
  - Add `dedoc/scramble` and the `client:generate` script.
- **Workbench**: `testbench.yaml` loses the Atrium provider, `atrium-assets` and the pennant migrations, and starts at `/`. `WorkbenchServiceProvider` loses `atrium.plugins`, sets `roster.routes.enabled` to true and configures Scramble.
- **Tests**:
  - `tests/Pest.php`'s `parityGaps()` checks `http` and `mcp` only. The `ui` surface and its `SyncOrganization*` exceptions go.
  - `TestCase` loses the Atrium provider, `openAtrium` and the plugin config.
  - Mixed tests that mention Atrium (`ArchTest`, `ComposerTest`, `GateTest`, `OrganizationScopedListsTest`, `RemovedSsoScimTest`, `SoftDeleteTest`, `Transfers/TemplateTest`, `UserApprovalTest`, `PerformanceTest`, `Web/*`, `PermissionCatalogueTest` and others) are edited to keep their non-UI assertions.
- **Dev dependencies**: Cortex and Impex stay in `require-dev`. If their v0.1 releases still pull in Atrium v1, it is installed transitively in dev only, and the arch guard (AC 14) is what proves Roster's own files do not name it.

### OpenAPI and client

- The Scramble config lives in the workbench (`WorkbenchServiceProvider`), because Scramble is dev-only and nothing in `src/` may depend on it.
  - The route filter keeps a route when its name starts with `roster.` and its middleware includes `api`, so the web routes are excluded.
  - `operationId` comes from the route name.
- `openapi.json` is pretty-printed with sorted keys, so that diffs stay stable.
- Response schemas come from Scramble's inference (D-014).
- The client ships TypeScript source with no build step (D-015).
  - `client/src/schema.d.ts` starts with the header `// openapi.json sha256: <hex>`, which the `generate` script writes.
  - `createRosterClient({ baseUrl, fetch?, headers?, credentials? })` returns `createClient<paths>(…)`.
  - `client/node_modules` is gitignored.

### Deviations (rows for `standards-feedback.md`)

- `http/authorization`: `LookUpInvitationRequest` overrides `authorize()` (public, token is the credential).
- `actions/actions-events` (Roster's "events carry the Data"): the lookup events omit the Data so the token never reaches listeners or the audit log.

## Out of scope

- Transfers' use of Impex classes (`StartImportAction`, `CancelTransferAction`): flagged in shape, no change, no spec (D-003).
- Atrium v2's port of the screens, widgets, search, invitation page and its own `viewAtrium` gate or `atrium.view` permission (Atrium spec).
- A settings-summary endpoint: Roster had no settings panel (D-008).
- An MCP tool for organization-wide role assignments, and MCP search tools (D-011, D-012).
- Pruning translation keys that only deleted code used; a Keystone bridge from resource `schema()` to OpenAPI (D-014); publishing the client to npm.
- Changing `roster.routes.enabled`'s default (D-001).

## Risks and open questions

- **Response typing**: Scramble cannot follow `$request->persist()`, so many response schemas may be untyped (`object`) in v2.0.0 and the client types will be weak for them. Follow-up: a Keystone-level bridge from each resource's `schema()` to OpenAPI. Needs its own Keystone spec.
- **Client install**: npm cannot install a git subdirectory. Atrium's Nuxt layer must use pnpm's `#v2.0.0&path:/client` (or a published package). Flag this to the Atrium spec.
- **Token in URLs**: the lookup puts the token in the request path, so host access logs can record it. Tokens expire (`roster.invitations.expires_after_days`) and only a signed-in invitee whose email matches can accept. The README tells hosts to keep such paths out of their logs.
- **Audit log volume**: if Keen audits the lookup events, guests can produce entries, capped by the rate limit. Neither entry carries the token.
- **Removing `atrium.view`/`viewAtrium`**: an app on Atrium v1 that relied on Roster's gate loses dashboard access when it upgrades. The CHANGELOG says so, and Atrium v2 owns its gate.
- **Keystone order**: the build needs the Keystone spec merged first. Until then, `composer update` may resolve a Keystone with `registerAtriumPlugin()`, which Roster does not call, so the risk is low.
