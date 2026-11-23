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
