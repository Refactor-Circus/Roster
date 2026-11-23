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

## D-019: Twelve out-of-list paths hold the rest of group 5's work; when are they committed?
- Choice: Committed now as a group 5 follow-up, as D-018 did for group 3.
- Rationale: Recommended option.
- Date: 2026-11-23
- Raised by: factory build; default chosen because the user asked to proceed without stopping

## D-020: Which version does the suite release under?
- Choice: Every suite package releases as v2.0.0 to match: Keystone, Atrium, Cortex, Impex, Keen, PennantPlus, Polycart, Roster and Showroom. Every `refactor-circus/*` constraint (require and require-dev) is `^2.0`, and every repo aliases `dev-main` to `2.0.x-dev`. Foundry and Guardian stay on Keystone `^0.1` until they migrate.
- Rationale: The user said "i'd like everything release as v2 to match". This supersedes any earlier choice to keep `^0.1` constraints or release Keystone as v0.2.0.
- Date: 2026-11-23
- Raised by: user

## D-021: Do roles and permissions stay in Roster?
- Choice: No. They move to Guardian. Roster asks Keystone's authorization contract (`Contracts\Permissions`, `Contracts\Roles`), and with no role store bound it falls back to Laravel's Gate and policies. Criterion 1 (organization role assignments) and D-011 are withdrawn.
- Rationale: User decision, 2026-11-23.
- Date: 2026-11-23
- Raised by: user

## D-022: Where do the organization owner rule and team-to-organization inheritance live?
- Choice: On the scope models. `OrganizationModel` and `TeamModel` implement Keystone's `Scope` (`grantsByItself()` for the owner rule, `parentScope()` for team to organization), and `GLOBAL_ONLY_PREFIXES` moves to `OrganizationModel::GLOBAL_ONLY_ABILITIES` with unchanged values.
- Rationale: Ownership is membership data that Roster owns, and every contract implementation honours `Scope`. Behaviour is the same with or without a role store. The README lists `roster.organizations.sync` as global-only but the code does not. The code is the behaviour, so the list moves unchanged and the README is corrected.
- Date: 2026-11-23
- Raised by: spec writer; conservative default

## D-023: How do Roster's impersonation blocks reach checks made by other packages?
- Choice: Roster decorates the bound `Permissions` with `ImpersonationAwarePermissions` through `$app->extend()`. The default blocked list replaces `roster.roles.manage` and `roster.roles.assign` with the patterns `*.roles.manage` and `*.roles.assign`, which `Str::is` already supports.
- Rationale: A role store's own API (role management) must stay blocked while someone is impersonating, but Roster must not name that package. Wildcards keep the protection without naming it.
- Date: 2026-11-23
- Raised by: spec writer; conservative default (security)

## D-024: What happens to the existing `roster_*` role tables?
- Choice: Roster deletes its create migration, stops touching those tables, and never drops them. Guardian's migration copies them (Guardian spec). The upgrade note tells hosts to drop them by hand once they have checked the copy.
- Rationale: The data is not destroyed when Guardian is installed late or not at all, and the two packages' migrations need no cross-package ordering.
- Date: 2026-11-23
- Raised by: spec writer; conservative default

## D-025: What do the fallback's unknowns mean for Roster?
- Choice: `abilities()` null means only super-admins may impersonate. `Roles::holders()` empty means approvals notify no one and do not fail. `Roles::exists()` false means the default member role is skipped and import role values are row errors.
- Rationale: Each refuses rather than grants. Impersonating a user whose powers cannot be listed could escalate.
- Date: 2026-11-23
- Raised by: spec writer; conservative default (security)

## D-026: How does the suite stay green while callers switch?
- Choice: Group 10 binds an interim adapter (Roster's existing role service behind Keystone's contracts). Group 11 moves the authorization tests onto Keystone's fakes. Group 12 switches role writes and reads. Group 13 deletes the domains and the adapter, enforced by an arch test.
- Rationale: Each group ends green, and no group mixes a rewrite of the tests with production changes.
- Date: 2026-11-23
- Raised by: spec writer; conservative default

## D-027: Which abilities does Roster still declare, and what about `lead`?
- Choice: Roster declares every former `roster.*` permission except `roster.roles.*`, which become the role store's own (`guardian.roles.*` in Guardian). `grantTo` covers `admin` and `member` only, so the team `lead` role is not declared. A role store that keeps adopted roles keeps `lead` with its grants.
- Rationale: Role management belongs to the role store. A default team role is a role-store concern, and a store with no team scope type cannot create it.
- Date: 2026-11-23
- Raised by: spec writer; conservative default

## D-028: Order with Guardian and spec review
- Choice: This addendum waits for Keystone's authorization addendum and blocks Guardian's v2 spec. Spec review is skipped on the user's instruction, so the status stays `approved`. The pace is build and gate, with no review rounds.
- Rationale: Guardian's v2 requires Roster `^2.0`, and Roster's `2.0.x-dev` alias only exists once this job merges. Guardian's current main stays on `^0.1` tags meanwhile.
- Date: 2026-11-23
- Raised by: user (pace, review); order defaulted by the spec writer
