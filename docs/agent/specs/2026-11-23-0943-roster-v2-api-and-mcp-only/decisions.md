# Decisions

## D-001: Does roster.routes.enabled keep defaulting to false?
- Choice: Yes. The API stays off by default; the config banner and README say so, and that the public invitation lookup follows the same flag.
- Rationale: Safe-by-default (architecture/configuration); turning it on needs host auth middleware, which Roster cannot choose. Same answer as the open Polycart question.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-002: New user-status endpoint, or map the UI's status form onto existing routes?
- Choice: No new endpoint. The old `UserUiController::status` was a pure dispatcher (active/suspended/deactivated → reactivate/suspend/deactivate, same `roster.users.manage-status` permission), so clients call `users.suspend`, `users.deactivate`, `users.reactivate`; README maps each status to its route.
- Rationale: Adds no surface; the three actions, events and MCP tools already exist.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-003: Transfers' Impex dependency
- Choice: Out of scope; no change and no follow-up spec from this one.
- Rationale: Unrelated to removing Atrium; the user asked to keep it out.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-004: One stats endpoint or one per permission?
- Choice: Two: `GET stats/users` (global roster.users.view) and `GET stats/organizations` (global roster.organizations.view), mirroring the two old widgets.
- Rationale: A request has one ability; a single endpoint would leak organization counts to users-only viewers or vice versa. `stats/...` avoids colliding with `users/{user}` and organization slugs.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-005: Search shape
- Choice: `GET search/users` and `GET search/organizations`, prefix match (as the Atrium sources did), `q` 1–100 chars, `limit` 1–25 default 5, LIKE wildcards escaped; users need global roster.users.view, organizations are limited to those the caller may view.
- Rationale: Same per-permission reasoning as D-004; keeps the old behaviour and index-friendly matching; escaping fixes a latent wildcard bug.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-006: Public invitation lookup design
- Choice: `GET {prefix}/invitations/{token}` (`roster.invitations.lookup`), registered from Invitation/public.php only when routes are enabled, with its own `roster.invitations.lookup_middleware` (default api + `throttle:roster-invitation-lookup`, 10/min per IP). Token constrained to 48 alphanumerics, looked up by hash; any non-pending/expired/unknown token gets one identical 404; payload is email, organization name, team names, expires_at; `Cache-Control: no-store`; events never carry the token; no MCP tool.
- Rationale: The token is the only credential, so the endpoint must not reveal whether a token ever existed, must be rate-limited, and must expose the minimum the page needs.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-007: What happens to the plain invitation page and impersonation banner views?
- Choice: Keep `invitations/show`, `impersonation-banner` and `partials/impersonation-banner-plain`, the `InvitationPage` contract and `PlainInvitationPage`; delete only `resources/views/ui/**`.
- Rationale: They are not Atrium views; hosts without a UI rely on the emailed link and banner. `roster.invitations.accept_url` plus a host binding already let Atrium v2 or the host own the page.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-008: Settings summary endpoint?
- Choice: None.
- Rationale: RosterPlugin declared no settings panel; nothing to port.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-009: viewAtrium gate, atrium.view permission and the 'atrium.' global-only prefix
- Choice: Remove all three from Roster; stored rows and grants stay (sync-permissions reports and keeps them); CHANGELOG tells hosts to define their own gate or use Atrium v2's.
- Rationale: Shape rule: nothing in src names Atrium; Atrium owns its access.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-010: Approval notification link (was the Atrium user page)
- Choice: New `roster.users.approvals.review_url` (default null, `{user}` placeholder); null sends no button. Impersonation return falls back to `/`.
- Rationale: Same pattern as `roster.invitations.accept_url`; host-chosen URL without naming Atrium.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-011: MCP for organization-wide role assignments?
- Choice: No new tool; the HTTP endpoint only.
- Rationale: ListRoleAssignmentsAction is already on MCP (parity satisfied); keeps scope minimal.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-012: MCP for stats and search?
- Choice: Add `show-user-stats-tool` and `show-organization-stats-tool`; exempt SearchUsersAction and SearchOrganizationsAction from MCP in PARITY_EXCEPTIONS (list tools already filter by `search`).
- Rationale: roster/action-surfaces requires MCP unless excepted with a reason; search is a typeahead for UIs.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-013: Browser suite, PennantPlus and the without-atrium CI job
- Choice: Delete the Browser suite and its tooling, drop refactor-circus/pennantplus (only RosterSupportFeature used it), and replace the without-atrium job with the ArchTest no-Atrium guard.
- Rationale: All of them exist only for the Atrium UI.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-014: OpenAPI response schemas
- Choice: Accept Scramble's inference for v2.0.0; a Keystone bridge from resource schema() to OpenAPI is a follow-up (its own Keystone spec).
- Rationale: Keeps this spec within Roster; the bridge is suite-wide.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-015: How the client is shipped
- Choice: Committed TypeScript source (no build), `@refactor-circus/roster-client` 2.0.0, `createRosterClient` wrapper; not published to npm.
- Rationale: Nuxt/Vite consume TS directly; Atrium pins a git tag (pnpm path syntax noted as a risk).
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-016: Atrium VCS repository entry in composer.json
- Choice: Remove it unless composer update needs it for a transitive dev dependency; the arch guard ignores `repositories`.
- Rationale: Repositories are tooling, not a dependency.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-017: Spec review
- Choice: Skipped; spec set to approved directly.
- Rationale: The user instructed to skip spec review and proceed.
- Date: 2026-11-23
- Raised by: write-spec; default chosen because the user asked to proceed without stopping

## D-018: Five out-of-list files hold group 3's work; when are they committed?
- Choice: Committed on the job branch as a group 3 follow-up (RosterPlugin, RosterServiceProvider, Authorization fixtures, OutputSchemaTest count 57, README rows). Group 5 drops the 'ui' parity entries.
- Rationale: Recommended option.
- Date: 2026-11-23
- Raised by: factory build; default chosen because the user asked to proceed without stopping
