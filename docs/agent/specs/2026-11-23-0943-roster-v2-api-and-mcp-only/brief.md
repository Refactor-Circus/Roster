Risk: high

## What changed
- Atrium removal: `src/Atrium`, `resources/views/ui/**`, the `atrium` config section, the `viewAtrium` gate, the `atrium.view` permission, `RosterSupportFeature`, `@rosterCan`, and all Atrium-only tests and browser tooling (Playwright, `browser-tests.yml`) are deleted. `composer.json` drops `refactor-circus/atrium`, `pennantplus` and `pest-plugin-browser`.
- New API: role assignments per organization, public invitation lookup, user and organization stats, and user and organization prefix search. Each has a Request, an Action, Data, events and a resource. Two MCP stats tools are added.
- New config: `invitations.lookup_middleware`, `rate_limit.invitation_lookups_per_minute`, `users.approvals.review_url`.
- OpenAPI and client: committed `openapi.json` (Scramble, dev only, workbench-configured) and a `client/` npm package (`@refactor-circus/roster-client`) with a `composer client:generate` script and a CI `client` job.
- Docs: README, CHANGELOG v2.0.0, Boost skill, AGENTS.md and local standards are updated.

## Why
Roster v2.0.0 ships only its JSON API and MCP server. The dashboard moves into Atrium v2, whose Nuxt layer needs the data the PHP screens used to compute (stats, search, role assignments, invitation page data). It gets that through a committed OpenAPI document and a generated client. Decisions D-001 to D-017 shaped this, including keeping `roster.routes.enabled` false by default, no MCP tools for search or role assignments, and Scramble's inferred schemas (D-014).

## Look at
- `src/Domains/Invitation/Http/Requests/LookUpInvitationRequest.php:30`: a public endpoint with `authorize()` returning true, one identical 404 for every unusable token, and `Cache-Control: no-store` at lines 54 and 57.
- `src/Domains/Invitation/InvitationServiceProvider.php:49`: the lookup route is registered on its own middleware (never `roster.routes.middleware`), only when routes are enabled and not cached.
- `src/RosterServiceProvider.php:207`: the `roster-invitation-lookup` per-IP limiter.
- `src/Domains/Invitation/Actions/LookUpInvitationAction.php`: lookup by hash, and events that omit the Data so the token never reaches listeners.
- `composer.json` and `config/roster.php`: the dependency removals and the new config keys.

## Tested
`composer test` passes (1114 tests, 100% coverage). `composer lint:check` and `ringside check` also pass.
- AC 1, 13: `OrganizationRoleAssignmentsHttpTest`, and the authorization tests for role assignments, user stats and search, and organization stats and search.
- AC 2-5: `InvitationLookupHttpTest` and `InvitationLookupActionTest`.
- AC 6, 25-27: `DocsTest`.
- AC 7-10: `UserStatsAndSearchHttpTest`, `OrganizationStatsAndSearchHttpTest` and `PrefixSearchTest`.
- AC 11: `ArchTest`.
- AC 12: `UserStatsToolTest`, `OrganizationStatsToolTest`, `OutputSchemaTest` and `parityGaps()` in `Pest.php`.
- AC 14-18: `ArchTest`, `AtriumScreensRemovedTest`, `ComposerTest` and `GateTest`.
- AC 16, 19: `InvitationPageTest`, `ImpersonationFlowTest` and `UserApprovalTest`.
- AC 21, 24: `OpenApiTest`.
- AC 22, 23: `client/package.json`, `client/src/index.ts` and the `composer.json` scripts. These have no dedicated test.

## Risks
- This is a breaking v2 change. Apps that relied on Roster's Atrium v1 screens, the `viewAtrium` gate or `atrium.view` lose them. The CHANGELOG upgrade note covers this.
- The public lookup puts the token in the URL path, so host access logs can record it. The README warns hosts about this. Guests can also generate audit entries (if Keen audits them), capped by the rate limit.
- Scramble cannot follow `$request->persist()`, so some response types in the client may be weak (`object`).
- The client cannot be installed from a git subdirectory with npm. Atrium must use pnpm or a published package, and this is flagged for the Atrium spec.
- The build needs the Keystone `drop-the-atrium-plugin-hook` spec merged first.
- Open findings: none (nits: 0). Broken packages: none.
