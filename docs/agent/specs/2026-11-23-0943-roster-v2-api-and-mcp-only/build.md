# Build: Roster v2: API and MCP only

## Group 1

Organization-wide role assignments endpoint (AC 1, 11, 13). Branch `ring-master/2026-11-23-0943-roster-v2-api-and-mcp-only--g1`.

### Files

- `src/Domains/Role/Http/Requests/IndexOrganizationRoleAssignmentsRequest.php` (new): extends `OrganizationRequest` (scope = the organization, unknown or deleted slug 404), ability `roster.roles.view`, `rules()` = `ListRoleAssignmentsData::rules()`, `persist()` calls `ListRoleAssignmentsAction` with `ListRoleAssignmentsData::fromValidated(['organization' => <slug>] + validated)` and answers `RoleAssignmentResource::collection(...)`.
- `src/Domains/Role/Http/Controllers/OrganizationRoleAssignmentController.php` (new): `index()` returns `$request->persist()`.
- `src/Domains/Role/routes.php`: `GET organizations/{organization}/role-assignments`, named `organizations.role-assignments.index` (so `roster.organizations.role-assignments.index`).
- `tests/Feature/Http/OrganizationRoleAssignmentsHttpTest.php` (new): organization and team assignments listed, globals and other organizations left out; pages like `ListRoleAssignmentsData::pagination()` (default per page, `per_page`/`page`, max + 1 is 422); each item equals the `GET users/{user}/roles` item for the same assignment; unknown and deleted organization 404.
- `tests/Authorization/OrganizationRoleAssignmentsAuthorizationTest.php` (new): guest 401; non-member and plain member 403; `admin` of another organization 403 here and 200 in their own; `admin` in the organization 200; deleted organization 404 for a super-admin.

Outside the group's file list (needed to keep existing suite tests green):

- `tests/Authorization/fixtures.php`: added the route to `apiRoutes()`, which `HttpAuthorizationTest` requires to cover every `roster.*` API route (it also runs the shared 401/403/super-admin checks on it).
- `README.md`: one row in the HTTP API table for the route, which `DocsTest` ("documents every API route in the README") requires. Group 8 rewrites the README and keeps it.

### Tests

Not run here; the gate runs `vendor/bin/pest`. `vendor/bin/pint --test` passes on the changed files.

### Deviations

None.

## Group 2

Public invitation lookup (AC 2, 3, 4, 5, 11, 12). Branch `ring-master/2026-11-23-0943-roster-v2-api-and-mcp-only--g2`, commit `c2603ad`.

### Files

- `src/Domains/Invitation/Data/LookUpInvitationData.php` (new): `final readonly`, `#[SensitiveParameter] string $token`, rules `required|string|size:48|alpha_num:ascii`.
- `src/Domains/Invitation/Actions/LookUpInvitationAction.php` (new): dispatches the starting event, then one query by `InvitationTokens::hash()` with `pending()` and `whereHas('organization')` (soft-deleted organizations excluded), loading `organization`; anything else throws `InvalidInvitationException::forToken()`. Loads the invited teams (id, name, ordered by name) into the `teamModels` relation, then dispatches the finished event. No writes, no transaction.
- `src/Domains/Invitation/Events/InvitationLookingUpActionEvent.php` (new, no payload) and `InvitationLookedUpActionEvent.php` (new, the invitation only).
- `src/Domains/Invitation/Resources/InvitationLookupResource.php` (new): final, `schema()`; `email`, `organization.name`, `teams` (sorted names), `expires_at`.
- `src/Domains/Invitation/Http/Requests/LookUpInvitationRequest.php` (new): `ability()` empty, `authorize()` true, `rules()` = `LookUpInvitationData::rules()` on the route `token`; `persist()` answers the resource with `Cache-Control: no-store`, or on `InvalidInvitationException` one fixed `{"message": "Not found."}` 404, also `no-store`.
- `src/Domains/Invitation/Http/Controllers/InvitationController.php`: `lookup()`.
- `src/Domains/Invitation/public.php` (new): `GET invitations/{token}`, `where('token', '[A-Za-z0-9]{48}')`, name `invitations.lookup`.
- `src/Domains/Invitation/InvitationServiceProvider.php`: `boot()` loads `public.php` only when `roster.routes.enabled === true` and routes are not cached, with prefix `roster.routes.prefix`, name prefix `roster.` and `roster.invitations.lookup_middleware` (never `roster.routes.middleware`).
- `src/RosterServiceProvider.php`: `registerRateLimiter()` adds `roster-invitation-lookup`, per IP, from `roster.rate_limit.invitation_lookups_per_minute` (default 10, non-numeric/null = `Limit::none()`).
- `config/roster.php`: `invitations.lookup_middleware` (`['api', 'throttle:roster-invitation-lookup']`) and `rate_limit.invitation_lookups_per_minute` (10), each documented in its banner (public, token only, follows `routes.enabled`, keep the limiter, keep the paths out of access logs); the HTTP API Routes banner says the lookup follows `enabled` and `prefix` but not `middleware`.
- `tests/Pest.php`: `invitationToken()` helper (stores an invitation by hash, returns the plain token); `PARITY_EXCEPTIONS` gains `LookUpInvitationAction` with its reason.
- `tests/Feature/Http/InvitationLookupHttpTest.php` (new): guest 200 exact payload + `no-store`; identical 404 for unknown/expired/revoked/accepted/declined/deleted-org; malformed tokens 404 with an empty query log; no token/hash/id/slug/inviter/status/other timestamps in the payload; serialized events free of the token; still 200 with `auth` in `roster.routes.middleware` (routes re-registered from config) while `invitations.accept` gets 401; prefix and lookup middleware from config; route absent while `routes.enabled` is false (the web page stays); 11th request per IP 429 with valid and invalid tokens, another IP unaffected; configurable limit, null off; independent of `rate_limit.per_minute`.
- `tests/Feature/InvitationLookupActionTest.php` (new): success with organization and teams loaded and both events; each refusal throws the same `InvalidInvitationException` with the starting event and no finished event; the stored hash used as a token is refused; the events carry no Data and no token; `LookUpInvitationData` rejects malformed tokens.
- `docs/agent/standards-feedback.md`: the two rows (authorize override; events without Data).

Outside the group's file list (needed to keep existing suite tests green):

- `README.md`: one row in the HTTP API table for `roster.invitations.lookup`, which `DocsTest` ("documents every API route in the README") requires. Group 8 rewrites the README and keeps it.

### Tests

Not run here; the gate runs `vendor/bin/pest`. `vendor/bin/pint --test` passes on the changed files and `phpstan` passes on `src/Domains/Invitation`, `src/RosterServiceProvider.php` and `config/roster.php`.

### Deviations

- `PARITY_EXCEPTIONS` lists `'LookUpInvitationAction' => ['mcp', 'ui']`, not just `['mcp']`: `parityGaps()` still checks the Atrium `ui` surface until group 5 removes it, and the lookup has no Roster UI caller. Group 5 should drop `'ui'` from this entry when it removes the surface (AC 12 holds either way: the action is listed under `mcp`).
- The standards deviations the spec names (http/authorization `authorize()` override; actions-events lookup events without Data) are logged in `docs/agent/standards-feedback.md`. `InvalidInvitationException` rather than a `ValidationException` follows the spec's design (domain-guards), since the request must turn every refusal into one 404.

## Group 3 (salvaged)

User stats and user search (AC 7, 9, 11, 12, 13). The build agent ended without a result; its work was in the tree, uncommitted. No index lock and no merge or rebase in progress.

### What was found

Every file in the group's list existed with the work for 3.1 to 3.4: `ShowUserStatsAction` (the counting moved verbatim from `RosterPlugin::statusCounts()`, cached 60 s under `Users::STATUS_COUNTS_CACHE`, now `roster:user-status-counts`; events, no transaction), `UserStatsResource` (final, `schema()` with every configured status required), `ShowUserStatsRequest`, `SearchUsersData` (`q` required 1-100, `limit` 1-25 default 5), `SearchUsersAction`, `SearchUsersRequest` (answers `UserSummaryResource`), `UserDirectoryController` (`stats`, `search`), the routes `stats/users` (`stats.users`) and `search/users` (`search.users`), the four events (all `ReadActionEvent`), `ShowUserStatsMcpRequest` and `ShowUserStatsTool` (`outputSchema()`), `RosterServer` listing it, `PARITY_EXCEPTIONS` entries, and the three test files.

Left broken:

- `src/Domains/User/routes.php` named `UserDirectoryController` without importing it, and `src/Mcp/RosterServer.php` listed `ShowUserStatsTool` without importing it. Both imports added.
- `SearchUsersAction` built its condition with `orWhereRaw()` on a non-literal string, which phpstan rejects (`argument.type`, literal-string). It now adds a `ConditionExpression` (`<wrapped column> like ? escape '!'`, the column wrapped by the query's grammar, as `Support\CaseInsensitive` does) and binds the pattern once per condition. Same SQL, same escaping.

### Committed

The group's listed paths only, with `tasks.md` and this note.

### Left uncommitted (outside the group's file list)

Five files outside the list are modified in the tree. Every hunk in them is this group's work (checked against HEAD), and the suite needs them, but the salvage could only commit the listed paths. They must be committed with this group, or the suite fails on the branch without them (`HttpAuthorizationTest`, `DocsTest`, `OutputSchemaTest`; `RosterPlugin` keeps working without its change):

- `src/Atrium/RosterPlugin.php`: `statusCounts()` now calls `ShowUserStatsAction` (task 3.2 moves the counting out of it).
- `src/RosterServiceProvider.php`: the listener that forgets the cached counts skips `ReadActionEvent`s, so a search (`UsersSearchedActionEvent`) keeps them.
- `tests/Authorization/fixtures.php`: the two routes in `apiRoutes()` (`HttpAuthorizationTest` covers every `roster.*` API route).
- `tests/Feature/Mcp/OutputSchemaTest.php`: 57 structured tools, up from 56.
- `README.md`: the two routes in the HTTP API table (`DocsTest`) and `show-user-stats-tool` in the MCP tools table. Group 8 rewrites the README.

`vendor/bin/pint --test` and `phpstan` pass on the changed files. The suite was not run here; the gate runs it.

### Deviations

- `PARITY_EXCEPTIONS` lists `SearchUsersAction => ['mcp', 'ui']` and `ShowUserStatsAction => ['ui']`: `parityGaps()` still checks the Atrium `ui` surface until group 5 removes it. Group 5 should drop `'ui'` from both entries and remove `ShowUserStatsAction` from the list (AC 12 holds: `SearchUsersAction` is listed under `mcp` with its reason).

### Left

- Commit the five files above with this group.
- 3.5: run `vendor/bin/pest` (the gate).

## Group 4

Organization stats and organization search (AC 8, 10, 11, 12, 13).

### Files

- `src/Domains/Organization/Actions/ShowOrganizationStatsAction.php` (new): the organizations widget's single query of three `count(*)` subqueries (organizations without the deleted, teams, `InvitationModel::pending()`), run on the organizations' connection (`getQuery()->newQuery()`), answered as `{organizations, teams, pending_invitations}` ints. Starting and finished events, no transaction (a read).
- `src/Domains/Organization/Actions/SearchOrganizationsAction.php` (new): `execute(SearchOrganizationsData $data, ?array $within = null)`; name or slug `like '<q>%' escape '!'` (`%`, `_` and `!` escaped, as `SearchUsersAction` does), deleted left out, `whereIn('id', $within)` when limited, ordered by name then id, `limit`, `links` and `owner` eager-loaded for `OrganizationResource`.
- `src/Domains/Organization/Data/SearchOrganizationsData.php` (new): `q` required 1-100, `limit` 1-25 default 5.
- `src/Domains/Organization/Events/OrganizationStatsShowingActionEvent.php`, `OrganizationStatsShownActionEvent.php` (carries the counts), `OrganizationsSearchingActionEvent.php`, `OrganizationsSearchedActionEvent.php` (carry the Data), all `ReadActionEvent`.
- `src/Domains/Organization/Http/Resources/OrganizationStatsResource.php` (new): final, `schema()` with the three counts required integers.
- `src/Domains/Organization/Http/Requests/ShowOrganizationStatsRequest.php` (global `roster.organizations.view`) and `SearchOrganizationsRequest.php` (`acrossOrganizations()`, `organizations()` passed as `$within`, answers `OrganizationResource::collection`).
- `src/Domains/Organization/Http/Controllers/OrganizationDirectoryController.php` (new): `stats`, `search`.
- `src/Domains/Organization/routes.php`: `GET stats/organizations` (`stats.organizations`) and `GET search/organizations` (`search.organizations`).
- `src/Domains/Organization/Mcp/Requests/ShowOrganizationStatsMcpRequest.php` and `Mcp/Tools/ShowOrganizationStatsTool.php` (new, `outputSchema()` = `OrganizationStatsResource::structuredSchema()`); `src/Mcp/RosterServer.php` lists the tool.
- `tests/Pest.php`: `PARITY_EXCEPTIONS` gains `SearchOrganizationsAction => ['mcp', 'ui']` and `ShowOrganizationStatsAction => ['ui']`, with reasons.
- Tests (new): `tests/Feature/Http/OrganizationStatsAndSearchHttpTest.php` (counts with deleted organization, deleted team, expired/revoked/accepted/declined invitations; zeroes; one count query; events; prefix on name or slug ordered by name; items equal the `organizations.show` fields; deleted left out; limit default 5 up to 25; `q`/`limit` validation; `%`, `_`, `\`, `!` literal, also in a slug; `$within`; Data default), `tests/Feature/Mcp/OrganizationStatsToolTest.php` (tool name, structured content equals the HTTP payload, also empty, output schema), `tests/Authorization/OrganizationStatsAndSearchAuthorizationTest.php` (401 guest and 403 without the permission on both routes; organization-level permission 403 on stats; search limited to the organizations where the caller holds the permission, another permission there opens nothing; global sees all; the tool needs the global permission).

Outside the group's file list (needed to keep existing suite tests green, as D-018 settled for group 3):

- `tests/Authorization/fixtures.php`: the two routes in `apiRoutes()` (`HttpAuthorizationTest` covers every `roster.*` API route).
- `tests/Feature/Mcp/OutputSchemaTest.php`: 58 structured tools, up from 57.
- `README.md`: the two routes in the HTTP API table (`DocsTest`) and `show-organization-stats-tool` in the MCP tools table. Group 8 rewrites the README.

### Tests

Not run here; the gate runs `vendor/bin/pest`. `vendor/bin/pint --test` passes on the changed files and `phpstan` passes on `src/Domains/Organization` and `src/Mcp/RosterServer.php`.

### Deviations

- `PARITY_EXCEPTIONS` lists `SearchOrganizationsAction => ['mcp', 'ui']` and `ShowOrganizationStatsAction => ['ui']`, for the same reason as group 3: `parityGaps()` still checks the Atrium `ui` surface. Group 5 should drop `'ui'` from the first and remove the second.
- The stats count is the widget's query as it was: teams are not soft-deleted, so a team of a deleted (soft-deleted, restorable) organization still counts, as does a pending invitation into one. AC 8 holds as written (records that are not deleted; invitations in `pending()`).
- `RosterPlugin`'s organizations widget keeps its own copy of the query rather than calling the action (it is deleted in group 5 and is outside the list).
- The LIKE helpers (`startsWith()`, `escape()`) repeat `SearchUsersAction`'s; sharing them would need a new `Support` class outside both groups' lists.

## Group 5 (salvaged)

Delete the Atrium screens and browser tooling (AC 15, 20). The build agent ended without a result; its work was in the tree, partly staged, uncommitted. No index lock and no merge or rebase in progress.

### What was found

Tasks 5.1 to 5.4 were done in the tree and consistent; nothing was left half-edited. `vendor/bin/pint --test` passes on every changed PHP file. A search of `src/`, `tests/`, `config/`, `workbench/`, `resources/` finds no remaining use of a deleted class, route, view or test case (what still names `atrium.roster.*` asserts it is absent, or is group 6's: `UserAwaitingApprovalNotification`, `ImpersonationWebController`, docs).

- 5.1: `ComposerTest` asserts no `extra.atrium`, no pennantplus in `require`/`require-dev`/`suggest`, and no browser tooling (pest-plugin-browser, `test:browser`, `--exclude-testsuite=Browser`, the `Browser` testsuite, root `package.json`/`package-lock.json`, `browser-tests.yml`, `browser_test`). The route and class checks are in a new `tests/Feature/AtriumScreensRemovedTest.php` (no `atrium.roster.*` route with the API mounted as control; `RosterPlugin`, `ScreenAccess`, `Badges`, `RosterSupportFeature` and the eight `*UiController`s absent; no `roster.atrium` config).
- 5.2: `RosterPlugin`, `Badges`, `ScreenAccess`, `Features/RosterSupportFeature`, the eight UI controllers and every screen view under `resources/views/ui` deleted, except `ui/invitations/show` and `ui/impersonation-banner` (neither includes a partial); `AuthorizesScreens` kept. The `roster.atrium` config section and its import gone. `atrium.plugins` (and the pennantplus store) gone from `WorkbenchServiceProvider`; `atrium.plugins` and the browser-only `$transactional` switch gone from `TestCase`. `tests/Pest.php` loses `ResetsScreenExtensions`, the Browser directory workaround, the Browser/AtriumFeatures/PennantPlus `uses()`, the `ui` parity surface and the `SyncOrganization*` exceptions, and `'ui'` from the `LookUpInvitationAction`, `SearchUsersAction` and `SearchOrganizationsAction` entries (`ShowUserStatsAction` and `ShowOrganizationStatsAction` dropped, as groups 3 and 4 asked). The listed Atrium-only tests and test cases deleted.
- 5.3: `OrganizationScopedListsTest` keeps the API/MCP assertions; its Atrium search test now checks `roster.search.organizations` (only the admin's organization, prefix match). `SoftDeleteTest`, `TemplateTest`, `RemovedSsoScimTest` drop their screen tests. `PerformanceTest` drops the two page-render tests and checks the cached counts through `ShowUserStatsAction`.
- 5.4: `phpunit.xml.dist` without the `Browser` testsuite; `composer.json` without pest-plugin-browser, pennantplus (`require-dev`, `suggest` and its VCS repository), `test:browser`, `--exclude-testsuite=Browser` and `extra.atrium`; `package.json`, `package-lock.json` and `browser-tests.yml` deleted; `browser_test` gone from `ring-master.yml`; the pennant migrations gone from `testbench.yaml`.

### Committed

The group's listed paths only, with `tasks.md` and this note.

### Left uncommitted (outside the group's file list)

These are this group's work too (groups 6 to 8 depend on 5, so nothing else writes this tree), and the suite needs them: without the first two the app fails to boot, since both name the deleted `ScreenAccess`; the tests below fail on deleted routes or classes. They must be committed with this group, as D-018 did for group 3:

- `src/Atrium/AtriumServiceProvider.php`: `@rosterCan` checks `Authorizer::check()` directly instead of `ScreenAccess::allows()`.
- `src/Atrium/Http/Controllers/Concerns/AuthorizesScreens.php`: only `screen()` and `overviewTab()` remain (the invitation page's), with the access closure inlined (global without a record, the invitation's organization with one, anything else refused).
- `tests/Feature/AtriumScreensRemovedTest.php` (new, untracked): the 5.1 route and class checks.
- `tests/Modes/Keen/OrganizationActivityRemovedTest.php` (deleted, staged): screen-only.
- `tests/ArchTest.php` (no `Workbench` use of the Atrium classes, no `ScreenAccess` rule, no Browser skip paths), `tests/Feature/Organization/MembershipSourceTest.php`, `tests/Feature/Permission/RegisteredResourcesTest.php`, `tests/Feature/UserApprovalTest.php`, `tests/Modes/Traitless/TraitlessUserTest.php`, `tests/Modes/TransfersUnavailable/TransfersUnavailableTest.php`: screen assertions dropped.
- `tests/Feature/Web/ImpersonationFlowTest.php`: leaving impersonation redirects to `/` now that `atrium.roster.users.show` is gone.
- `.github/workflows/tests.yml` (Windows step runs bare `vendor/bin/pest`) and `.gitignore` (no `/node_modules`, `tests/Browser/*` entries).

### Tests

Not run here; the gate runs `vendor/bin/pest`. `pestphp/pest-plugin-browser` is still in `vendor/` until `composer update`, which is harmless: its directory cleanup only runs for browser tests.

### Left

- Commit the files above with this group.
- 5.5: run `vendor/bin/pest` (the gate).

## Group 6 (salvaged)

Remove the Atrium seam and every remaining reference (AC 14–19). The first agent ended without a result. There was no index lock and no merge or rebase.

### Found

Most of 6.2–6.4 was done but uncommitted:

- `src/Atrium/**` and `resources/views/ui/**` deleted (staged).
- The seam, `$withoutAtrium` and the docblocks removed from `RosterServiceProvider`.
- The banner includes only the plain partial.
- Atrium wording stripped from the domain and support files.
- No `viewAtrium` gate, no `atrium.view` built-in, and no `'atrium.'` global-only prefix.
- `roster.users.approvals.review_url` added (banner and default `null`) and used by `UserAwaitingApprovalNotification`, with `{user}` replaced by the route key.
- `ImpersonationWebController::returnTo()` returns `return_to` or `/`.
- Config banners rewritten.
- composer.json without `refactor-circus/atrium` (`require-dev`, `suggest`, `conflict`) or its VCS repository. Cortex, Impex and Keen have Atrium in `require-dev` only, so nothing needs it transitively (D-016).
- testbench.yaml without the Atrium provider or `atrium-assets`, starting at `/`.
- The `without-atrium` CI job removed.

The mixed tests were rewritten: `GateTest`, `CommandsTest` (a stored `atrium.view` is reported and kept with its grants), `PermissionsTest`, `RoleActionsTest`, `RolesHttpTest`, `PermissionCatalogueTest`, `UserApprovalTest` (review URL or no button), `ImpersonationFlowTest` (plain banner, `return_to` or `/`) and `InvitationPageTest` (`PlainInvitationPage`, a host binding wins, `accept_url` overrides the link). `lang/` and `src/Support/Users.php` had no Atrium text.

### Finished in this salvage

- `tests/ArchTest.php`:
  - New guard: no line naming `atrium` (case-insensitive) in `src/`, `config/`, `database/`, `lang/`, `resources/views/`, `workbench/` (not `workbench/storage` or `workbench/bootstrap/cache`) or `testbench.yaml`.
  - No `atrium` key in composer `require`/`require-dev`/`suggest`/`conflict`/`extra`.
  - No `src/Atrium` folder, and no `RefactorCircus\Atrium` use in `RefactorCircus\Roster`.
  - `resources/views` holds exactly the three plain views.
  - A self-test of the guard on a temporary folder.
  - The seam rules (`rosterAtriumClasses()`, seam-only-in-provider, the class-name string check, `x-atrium::audit`) are removed. The parity rule is retitled "HTTP API and MCP".
- `TestCase`: lost the Atrium provider, `$openAtrium` with its `viewAtrium` gate, and `atrium.disabled`. It sets `impex.ui.enabled` and `cortex.ui.enabled` to false: Atrium v1 stays in `vendor/` through the siblings' dev dependencies, and with the guard passing their providers would otherwise register with an Atrium whose provider no longer boots.
- `AuthorizationTestCase`: lost `$openAtrium`.
- `tests/Pest.php`: lost the `AtriumUnavailable` mode.
- Deleted `tests/Modes/AtriumUnavailable/**`, `tests/AtriumUnavailableTestCase.php` and `tests/Feature/AtriumSeamTest.php`.
- `ComposerTest`: no Atrium in `require-dev` or `suggest`, no `conflict` section, no Atrium VCS repository.
- `RegisteredResourcesTest`: the `@rosterCan` renders are replaced by an assertion that no `rosterCan` directive is registered. The Authorizer assertions are kept.
- `ImpersonationFlowTest`: asserts no `rosterUsesAtrium` view share.
- `DocsTest` and the listed Traitless, TransfersUnavailable and MembershipSource tests needed nothing: the removal does not break them. The README text they read is group 8's.

### Outside the file list

`resources/views/invitations/show.blade.php` and `resources/views/partials/impersonation-banner-plain.blade.php`: only their header comments changed, to drop the word Atrium. The AC 14 guard over `resources/views/` fails without that. They are committed with this group.

### Tests

Not run here; the gate runs `vendor/bin/pest`. `php -l` passes on the edited tests.

### Left

- 6.5: run `vendor/bin/pest` (the gate).

## Group 6

This retry finishes 6.5. Tasks 6.1–6.4 are in the salvaged commit above and are kept as they are.

### Files

- `tasks.md`: 6.5 ticked.
- No code or test files changed in this retry.

### Checks before handing to the gate (the suite was not run)

- No file in `src/`, `config/`, `database/`, `lang/`, `resources/views/`, `workbench/`, `testbench.yaml` or `composer.json` mentions `atrium` (case-insensitive grep). This is what the AC 14 ArchTest guard requires.
- No test still uses `$withoutAtrium`, `$openAtrium`, the `AtriumUnavailable` mode or a deleted `RefactorCircus\Roster\Atrium` class. The remaining hits are deliberate:
  - absence assertions in `AtriumScreensRemovedTest`, `RemovedSsoScimTest`, `GateTest` and `ImpersonationFlowTest`;
  - the string-only parser self-test in `DocsTest`.
- `DocsTest` still asserts README text that mentions Atrium: `ScreenAccess::allowsOn` and `tests/Modes/AtriumUnavailable` under "Testing". README still contains that text, so these pass until group 8 rewrites README and `DocsTest` together.
- The class-existence check in `DocsTest` covers only "Extending Roster". README's `RosterSupportFeature` example is under "Atrium dashboard", so the check doesn't reach it.

### Tests

The gate runs `vendor/bin/pest`.

### Deviations

None.

## Group 7

OpenAPI export and TypeScript client (AC 21–24). Tasks 7.1–7.5 are ticked; the gate runs the suite (7.5).

### Files

- `tests/Feature/OpenApiTest.php` (new). It checks:
  - the committed operations equal the registered `roster.*` routes in the `api` middleware group, as `operationId METHOD /path`. The list includes the six new routes and `roster.history.index`.
  - no operation exists for the six web routes (`roster.invitations.show`, `roster.invitations.page.*`, `roster.impersonation.*`, `roster.transfers.file`), and the test first asserts those routes are registered.
  - the document is OpenAPI 3.1, `info.title` "Roster API", `info.version` "2.0.0".
  - a fresh `workbench:openapi` export is byte-identical to `openapi.json`.
  - `openapi.json` is pretty-printed, has sorted keys and ends with a newline.
  - the first line of `schema.d.ts` is `// openapi.json sha256: <hex of openapi.json>`.
  - `client/package.json`: name, version, deps, scripts, lockfile and `scripts/generate.mjs`.
  - `index.ts` exports `createRosterClient` with the four options, calls `createClient<paths>(` and re-exports `paths` and `components`.
  - Scramble is in `require-dev` only, and `client:generate` holds the expected steps.
  - nothing in `src/` names `Dedoc\Scramble`.
  - the CI `client` job exists.
  - `.gitignore` lists `/client/node_modules`.
- `composer.json`:
  - adds `dedoc/scramble` `^0.13.55` (require-dev), the version Cortex's job uses.
  - adds `client:generate`, which runs `@php vendor/bin/testbench workbench:openapi openapi.json`, then `npm --prefix client ci`, then `npm --prefix client run generate`.
- `workbench/app/Providers/WorkbenchServiceProvider.php`:
  - keeps `roster.routes.enabled` = true.
  - After boot, it configures Scramble:
    - `api_path` = `roster.routes.prefix`;
    - version "2.0.0", a description, `ui.title` "Roster API";
    - a route filter: name starts with `roster.` and route middleware includes `api`;
    - `operationId` = route name;
    - one relative server, `/roster`, so the document doesn't depend on `APP_URL`.
  - Registers the closure command `workbench:openapi {path=openapi.json}`. It migrates a fresh in-memory SQLite database, because Scramble reads model columns. It runs `scramble:export`, then rewrites the file with every object's keys sorted recursively, keeping objects as objects and lists in order. The output is pretty-printed, unescaped slashes, with a trailing newline.
- `openapi.json` (new, generated): 73 operations, OpenAPI 3.1.2. Two exports in a row were byte-identical.
- `client/` (new):
  - `package.json`: `@refactor-circus/roster-client` 2.0.0, private, TS source only (D-015). `openapi-fetch` is in dependencies; `openapi-typescript` and `typescript` are in devDependencies. Scripts: `generate` (`node scripts/generate.mjs`) and `typecheck` (`tsc --noEmit`).
  - `package-lock.json` and `tsconfig.json`.
  - `scripts/generate.mjs`: writes `src/schema.d.ts` with the sha256 header line, then a "generated, do not edit" line.
  - `src/index.ts`: `createRosterClient({ baseUrl, fetch?, headers?, credentials? })` returns `createClient<paths>(options)`. It re-exports `paths`, `components` and `operations`.
  - `src/schema.d.ts` (generated). `npm run typecheck` passes.
- `.gitignore`: `/client/node_modules`.
- `.github/workflows/tests.yml`: a `client` job on Node 22 (setup-node pinned by SHA like the sibling packages) that runs `npm ci`, `npm run generate`, `npm run typecheck` and `git diff --exit-code client/`.

### Tests

- `tests/Feature/OpenApiTest.php` was run alone: 12 passed. Pint passes on both PHP files. The gate runs the full suite.
- Scramble's export reports two `JR001` warnings ("Cannot infer the resource model") for `UserStatsResource` and `OrganizationStatsResource`. Those two responses are weakly typed, which D-014 accepts.

### Deviations

- `client:generate` runs `npm --prefix client ci` before `generate`. Without it, a fresh checkout has no `openapi-typescript` to run. Cortex's script does the same. No standard is affected.
- The export goes through the workbench command `workbench:openapi` instead of a bare `scramble:export`. Scramble cannot sort keys or pin the database, and the spec requires a key-sorted, deterministic `openapi.json`. The command is a closure in `WorkbenchServiceProvider`, so no file outside the group's list was added.

### Retry check

The retry found every group 7 task already committed (a279bf0), so it changed no code. It checked that work again:
- `tests/Feature/OpenApiTest.php` run alone: 12 passed, 59 assertions.
- `npm --prefix client run generate` left no diff, and `npm --prefix client run typecheck` passes.
- Pint passes on `OpenApiTest.php` and `WorkbenchServiceProvider.php`.

## Group 8 (salvaged)

Docs, changelog and standards (AC 6, 25, 26, 27). The build agent ended without a result; its work was in the tree, uncommitted (the `roster/atrium-views` deletion staged). There was no index lock and no merge or rebase in progress.

### What was found

Every listed file held finished work for 8.1 to 8.3, and nothing was half-edited (`git diff --check` is clean):

- `tests/Feature/DocsTest.php` (8.1):
  - The README intro names a JSON API and MCP server, with Atrium v2 as the optional UI. Neither the README nor the Boost skill has the "Atrium dashboard", "Screen extensions", "Registered tabs" or "MCP redirect domains" headings, or any Atrium v1 term.
  - The six endpoints are rows of the HTTP API table.
  - "Changing a user's status" maps `active`, `suspended` and `deactivated` to their routes, requests and tools.
  - "Public invitation lookup" states the security model.
  - The README says `roster.routes.enabled` defaults to `false`.
  - "OpenAPI document and TypeScript client" covers regeneration and use.
  - The CHANGELOG v2.0.0 has the Breaking upgrade note and the Added list.
  - The agent guides name Atrium only as Atrium v2, and they document the endpoints and `composer client:generate`.
  - The local standards are free of the UI. `roster/atrium-views` is gone.
  - The two deviation rows and the resolved 2026-10-30 row are in standards-feedback.
- `README.md` and `CHANGELOG.md` (8.2):
  - The README is rewritten as an API and MCP package. It has the six rows, the status table, the lookup section, the OpenAPI/client section and the new MCP tools.
  - The CHANGELOG has a `v2.0.0` entry with Breaking (removed classes, `atrium.roster.*` routes, `ui/` views, `roster.atrium`, `@rosterCan`/`rosterUsesAtrium`, `viewAtrium`/`atrium.view`, `RosterSupportFeature`, pennantplus, the links now in `roster.users.approvals.review_url`/`roster.impersonation.return_to`, the renamed status-counts cache key, the browser suite) and Added (endpoints, MCP tools, config keys, `openapi.json`, the client, `composer client:generate`).
- 8.3:
  - **Boost skill:** the Atrium architecture text is gone. The new section 12 covers the API for a UI, OpenAPI and the client.
  - **`AGENTS.md`** (which `CLAUDE.md` links to): no Atrium architecture and no `composer test:browser`. It has the new endpoints, the lookup and the `client:generate` workflow.
  - **Local standards:** `roster/atrium-views` is deleted. `roster/action-surfaces` is rewritten for HTTP and MCP, `roster/optional-surfaces` gains the lookup row in place of the dashboard, `roster/testbench-environment` has the new provider list and modes, and `http/authorization` drops the Atrium, `ScreenAccess` and `@rosterCan` text.
  - **`standards-feedback.md`:** the 2026-10-30 plain-views row is marked Resolved 2026-11-23.

### Checked by hand (the suite was not run)

- The DocsTest needles were compared against the README, CHANGELOG, skill, `AGENTS.md` and standards text.
- No Atrium v1 term or SSO/SCIM token is in the guides.
- The README "Extending Roster" needles and ArchTest's old-tab-registry strings still hold.
- `composer.json` has no Atrium or pennantplus entry, so the CHANGELOG's dependency claim is true.
- `composer.json`'s `client:generate` matches the README description.

### Committed

The group's listed paths, with `tasks.md` and this note. `CLAUDE.md` is a symlink to `AGENTS.md` and had no change of its own.

### Left

- 8.4: run `vendor/bin/pest` (the gate).

## Group 8

This retry finishes task 8.4. Tasks 8.1 to 8.3 were already committed in d638374 (see "Group 8 (salvaged)" above), and this retry kept them unchanged.

- **Files:** only `tasks.md` (8.4 ticked) and this note. No code, docs, standards or test files changed.
- **Tests:** no new tests. `tests/Feature/DocsTest.php` from 8.1 covers AC 6, 25, 26 and 27. As instructed, this retry did not run the suite; 8.4 (`vendor/bin/pest`) is the gate that runs after the build.
- **Deviations:** none beyond the two invitation-lookup rows already in `docs/agent/standards-feedback.md`.
