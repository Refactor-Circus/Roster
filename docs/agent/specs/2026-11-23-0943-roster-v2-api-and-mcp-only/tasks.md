# Tasks: Roster v2: API and MCP only

New API first (groups 1–4), then the Atrium removal (5–6), then OpenAPI/client (7) and docs (8). Groups run in order; each ends with the suite green.

## Group 1: Organization-wide role assignments endpoint
depends on: none
files: src/Domains/Role/routes.php, src/Domains/Role/Http/Controllers/OrganizationRoleAssignmentController.php, src/Domains/Role/Http/Requests/IndexOrganizationRoleAssignmentsRequest.php, tests/Feature/Http/OrganizationRoleAssignmentsHttpTest.php, tests/Authorization/OrganizationRoleAssignmentsAuthorizationTest.php
criteria: 1, 11, 13

- [x] 1.1 Write tests: org + team assignments listed and paginated, items equal `GET users/{user}/roles` items, unknown/deleted org 404, guest 401, no `roster.roles.view` in the org 403, permission in another org 403
- [x] 1.2 Add `IndexOrganizationRoleAssignmentsRequest` (extends `OrganizationRequest`, ability `roster.roles.view`, persist via `ListRoleAssignmentsAction` with the organization) and `OrganizationRoleAssignmentController::index`; route `GET organizations/{organization}/role-assignments` named `organizations.role-assignments.index`
- [x] 1.3 Run vendor/bin/pest; all green

## Group 2: Public invitation lookup
depends on: none
files: src/Domains/Invitation/public.php, src/Domains/Invitation/InvitationServiceProvider.php, src/Domains/Invitation/Actions/LookUpInvitationAction.php, src/Domains/Invitation/Data/LookUpInvitationData.php, src/Domains/Invitation/Events/InvitationLookingUpActionEvent.php, src/Domains/Invitation/Events/InvitationLookedUpActionEvent.php, src/Domains/Invitation/Http/Requests/LookUpInvitationRequest.php, src/Domains/Invitation/Http/Controllers/InvitationController.php, src/Domains/Invitation/Resources/InvitationLookupResource.php, src/RosterServiceProvider.php, config/roster.php, tests/Pest.php, tests/Feature/Http/InvitationLookupHttpTest.php, tests/Feature/InvitationLookupActionTest.php, docs/agent/standards-feedback.md
criteria: 2, 3, 4, 5, 11, 12

- [x] 2.1 Write tests: guest 200 with exactly `{email, organization:{name}, teams:[sorted names], expires_at}` and `Cache-Control: no-store`; identical 404 (status and body) for unknown, expired, revoked, accepted, declined and deleted-org tokens; malformed token 404 with no query (`DB::enableQueryLog`); still 200 with `auth` in `roster.routes.middleware`; 11th request per IP within a minute 429 with valid and invalid tokens; route absent when `roster.routes.enabled` is false; payload has no token/token_hash/id/slug/inviter/status; serialized starting and finished events do not contain the plain token; action-level tests for each refusal
- [x] 2.2 Add `LookUpInvitationData` (token), `LookUpInvitationAction` (hash lookup, pending + unexpired + live org guard, throws `InvalidInvitationException::forToken()`) and its two events (no Data, no token)
- [x] 2.3 Add `InvitationLookupResource` (final, `schema()`), `LookUpInvitationRequest` (`authorize()` true, 404 on `InvalidInvitationException`, `no-store` header), `InvitationController::lookup`, `public.php` route with `where('token', '[A-Za-z0-9]{48}')`, loaded by `InvitationServiceProvider` only when `roster.routes.enabled === true` and routes are not cached, with prefix `roster.routes.prefix`, name `roster.invitations.lookup`, middleware `roster.invitations.lookup_middleware`
- [x] 2.4 Add the `roster-invitation-lookup` limiter (per IP, `roster.rate_limit.invitation_lookups_per_minute`, default 10, null off) in `RosterServiceProvider::registerRateLimiter()`; add `invitations.lookup_middleware` and the limit to `config/roster.php` with banner text (public, token-only, follows `routes.enabled`)
- [x] 2.5 Add `LookUpInvitationAction => ['mcp']` with its reason to `PARITY_EXCEPTIONS`; add the two standards-feedback rows (authorize override, events without Data)
- [x] 2.6 Run vendor/bin/pest; all green

## Group 3: User stats and user search
depends on: 2
files: src/Domains/User/routes.php, src/Domains/User/Http/Controllers/UserDirectoryController.php, src/Domains/User/Http/Requests/ShowUserStatsRequest.php, src/Domains/User/Http/Requests/SearchUsersRequest.php, src/Domains/User/Http/Resources/UserStatsResource.php, src/Domains/User/Actions/ShowUserStatsAction.php, src/Domains/User/Actions/SearchUsersAction.php, src/Domains/User/Data/SearchUsersData.php, src/Domains/User/Events/UserStatsShowingActionEvent.php, src/Domains/User/Events/UserStatsShownActionEvent.php, src/Domains/User/Events/UsersSearchingActionEvent.php, src/Domains/User/Events/UsersSearchedActionEvent.php, src/Domains/User/Mcp/Requests/ShowUserStatsMcpRequest.php, src/Domains/User/Mcp/Tools/ShowUserStatsTool.php, src/Mcp/RosterServer.php, src/Support/Users.php, tests/Pest.php, tests/Feature/Http/UserStatsAndSearchHttpTest.php, tests/Feature/Mcp/UserStatsToolTest.php, tests/Authorization/UserStatsAndSearchAuthorizationTest.php
criteria: 7, 9, 11, 12, 13

- [x] 3.1 Write tests: stats `total` + `by_status` for every configured status (profile-less users active), cached under `roster:user-status-counts` and forgotten when a User action finishes; search prefix match on name/email columns, `q` 1–100 required, `limit` 1–25 default 5, `%`/`_`/`\` literal; MCP tool structured content equals HTTP payload and matches its output schema; guest 401, missing global `roster.users.view` 403 on both routes
- [x] 3.2 Move the status counting out of `RosterPlugin::statusCounts()` into `ShowUserStatsAction` (events, no transaction); rename `Users::STATUS_COUNTS_CACHE` to `roster:user-status-counts`; add `UserStatsResource`, `ShowUserStatsRequest`, `UserDirectoryController::stats`, route `stats/users` (`stats.users`)
- [x] 3.3 Add `SearchUsersData`, `SearchUsersAction` (escaped LIKE, `Users::column()`), `SearchUsersRequest` answering `UserSummaryResource`, `UserDirectoryController::search`, route `search/users` (`search.users`)
- [x] 3.4 Add `ShowUserStatsMcpRequest` + `ShowUserStatsTool` with `outputSchema()`, list it in `RosterServer`; add `SearchUsersAction => ['mcp']` with reason to `PARITY_EXCEPTIONS`
- [x] 3.5 Run vendor/bin/pest; all green

## Group 4: Organization stats and organization search
depends on: 3
files: src/Domains/Organization/routes.php, src/Domains/Organization/Http/Controllers/OrganizationDirectoryController.php, src/Domains/Organization/Http/Requests/ShowOrganizationStatsRequest.php, src/Domains/Organization/Http/Requests/SearchOrganizationsRequest.php, src/Domains/Organization/Http/Resources/OrganizationStatsResource.php, src/Domains/Organization/Actions/ShowOrganizationStatsAction.php, src/Domains/Organization/Actions/SearchOrganizationsAction.php, src/Domains/Organization/Data/SearchOrganizationsData.php, src/Domains/Organization/Events/OrganizationStatsShowingActionEvent.php, src/Domains/Organization/Events/OrganizationStatsShownActionEvent.php, src/Domains/Organization/Events/OrganizationsSearchingActionEvent.php, src/Domains/Organization/Events/OrganizationsSearchedActionEvent.php, src/Domains/Organization/Mcp/Requests/ShowOrganizationStatsMcpRequest.php, src/Domains/Organization/Mcp/Tools/ShowOrganizationStatsTool.php, src/Mcp/RosterServer.php, tests/Pest.php, tests/Feature/Http/OrganizationStatsAndSearchHttpTest.php, tests/Feature/Mcp/OrganizationStatsToolTest.php, tests/Authorization/OrganizationStatsAndSearchAuthorizationTest.php
criteria: 8, 10, 11, 12, 13

- [x] 4.1 Write tests: stats counts non-deleted organizations, teams and `pending()` invitations; search prefix on name/slug ordered by name, deleted excluded, same `q`/`limit`/literal rules, caller with `roster.organizations.view` in some organizations sees only those; MCP tool equals HTTP payload; guest 401, missing permission 403
- [x] 4.2 Add `ShowOrganizationStatsAction` (the widget's single three-subquery count), `OrganizationStatsResource`, `ShowOrganizationStatsRequest` (global `roster.organizations.view`), `OrganizationDirectoryController::stats`, route `stats/organizations` (`stats.organizations`)
- [x] 4.3 Add `SearchOrganizationsData`, `SearchOrganizationsAction` (escaped LIKE, `$within`), `SearchOrganizationsRequest` (`acrossOrganizations()`, answers `OrganizationResource`), `OrganizationDirectoryController::search`, route `search/organizations` (`search.organizations`)
- [x] 4.4 Add `ShowOrganizationStatsMcpRequest` + `ShowOrganizationStatsTool`, list it in `RosterServer`; add `SearchOrganizationsAction => ['mcp']` with reason to `PARITY_EXCEPTIONS`
- [x] 4.5 Run vendor/bin/pest; all green

## Group 5: Delete the Atrium screens and browser tooling
depends on: 4
files: src/Atrium/RosterPlugin.php, src/Atrium/Badges.php, src/Atrium/ScreenAccess.php, src/Atrium/Features/RosterSupportFeature.php, src/Atrium/Http/Controllers/*UiController.php, resources/views/ui/** (except ui/invitations/show and ui/impersonation-banner and the partials they include), composer.json (extra.atrium, pennantplus, pest-plugin-browser, test:browser, --exclude-testsuite=Browser), config/roster.php (atrium section), workbench/app/Providers/WorkbenchServiceProvider.php, testbench.yaml (pennant migrations), phpunit.xml.dist, package.json, package-lock.json, .github/workflows/browser-tests.yml, ring-master.yml, tests/TestCase.php, tests/Pest.php, tests/Feature/Ui/**, tests/Browser/**, tests/BrowserTestCase.php, tests/Feature/NavigationSectionTest.php, tests/Modes/AtriumFeatures/**, tests/AtriumFeaturesTestCase.php, tests/Modes/PennantPlus/**, tests/PennantPlusTestCase.php, tests/Fixtures/Features/OrphanFeature.php, tests/Authorization/AtriumAuthorizationTest.php, tests/Authorization/ScreenControlsTest.php, tests/Authorization/ScreenRegistryResetTest.php, tests/Authorization/OrganizationScopedListsTest.php, tests/Feature/SoftDeleteTest.php, tests/Feature/Transfers/TemplateTest.php, tests/Feature/RemovedSsoScimTest.php, tests/Feature/PerformanceTest.php, tests/Feature/ComposerTest.php
criteria: 15, 20

- [x] 5.1 Write tests: no route named `atrium.roster.*`; `RosterPlugin`/`ScreenAccess`/`RosterSupportFeature` classes absent; `composer.json` has no `extra.atrium`, pennantplus or pest-plugin-browser (`ComposerTest`)
- [x] 5.2 Delete the plugin, UI controllers (keep `Http/Controllers/Concerns/AuthorizesScreens.php` until group 6), `ScreenAccess`, `Badges`, `RosterSupportFeature`, the `roster.atrium` config section, the screen views under `resources/views/ui`, and the Atrium-only tests listed above; remove `atrium.plugins` from `WorkbenchServiceProvider` and `TestCase`; remove `ResetsScreenExtensions` and the `ui` surface (and `SyncOrganization*` exceptions) from `tests/Pest.php`
- [x] 5.3 Edit the mixed tests (`OrganizationScopedListsTest`, `SoftDeleteTest`, `TemplateTest`, `RemovedSsoScimTest`, `PerformanceTest`) to keep their API/MCP/action assertions and drop screen assertions
- [x] 5.4 Remove browser tooling: `Browser` testsuite, `test:browser`, `--exclude-testsuite=Browser`, pest-plugin-browser, root `package.json`/`package-lock.json`, `browser-tests.yml`, `browser_test` in `ring-master.yml`; drop pennantplus from `require-dev`/`suggest` and the pennant migrations from `testbench.yaml`
- [x] 5.5 Run vendor/bin/pest; all green

## Group 6: Remove the Atrium seam and every remaining reference
depends on: 5
files: src/Atrium/** (remaining: AtriumServiceProvider.php, InvitationPage.php, Http/Controllers/Concerns/AuthorizesScreens.php), src/RosterServiceProvider.php, src/Domains/Invitation/InvitationServiceProvider.php, src/Domains/Invitation/Contracts/InvitationPage.php, src/Domains/Invitation/Services/PlainInvitationPage.php, src/Domains/Permission/PermissionServiceProvider.php, src/Domains/Permission/Services/Permissions.php, src/Domains/Permission/Services/Authorizer.php, src/Domains/Role/Support/BuiltInRoles.php, src/Domains/User/Notifications/UserAwaitingApprovalNotification.php, src/Domains/Impersonation/Http/Controllers/ImpersonationWebController.php, src/Domains/Organization/Actions/PurgeOrganizationAction.php, src/Contracts/Status.php, src/Support/Users.php, src/Support/Policies/Policy.php, resources/views/ui/**, resources/views/impersonation-banner.blade.php, lang/**, config/roster.php, composer.json (require-dev, suggest, conflict, repositories), testbench.yaml, .github/workflows/tests.yml (without-atrium job), tests/ArchTest.php, tests/TestCase.php, tests/AuthorizationTestCase.php, tests/Pest.php, tests/Modes/AtriumUnavailable/**, tests/AtriumUnavailableTestCase.php, tests/Feature/AtriumSeamTest.php, tests/Feature/ComposerTest.php, tests/Feature/UserApprovalTest.php, tests/Feature/Web/ImpersonationFlowTest.php, tests/Feature/Web/InvitationPageTest.php, tests/Authorization/GateTest.php, tests/Authorization/CommandsTest.php, tests/Authorization/PermissionsTest.php, tests/Authorization/RoleActionsTest.php, tests/Unit/Permission/PermissionCatalogueTest.php, tests/Feature/Http/RolesHttpTest.php, tests/Feature/Organization/MembershipSourceTest.php, tests/Feature/Permission/RegisteredResourcesTest.php, tests/Modes/Keen/OrganizationActivityRemovedTest.php, tests/Modes/Traitless/TraitlessUserTest.php, tests/Modes/TransfersUnavailable/TransfersUnavailableTest.php, tests/Feature/DocsTest.php (only what removal breaks)
criteria: 14, 15, 16, 17, 18, 19

- [x] 6.1 Write tests: ArchTest guard (no `atrium`, case-insensitive, in src/, config/, database/, lang/, resources/views/, workbench/, testbench.yaml; no atrium key in composer `require`/`require-dev`/`suggest`/`conflict`/`extra`); no `rosterCan` directive or `rosterUsesAtrium` share; `resources/views` holds exactly the three plain views and the banner renders the plain one; signed invitation page renders via `PlainInvitationPage` and a host binding wins; `accept_url` still overrides the email link; no `viewAtrium` gate and no `atrium.view` built-in permission, stored `atrium.view` kept by `roster:sync-permissions`; approval email uses `roster.users.approvals.review_url` (`{user}` replaced) or has no button; impersonation leave returns to `return_to` or `/`
- [x] 6.2 Delete the remaining `src/Atrium` files and `resources/views/ui`; remove the seam, `$withoutAtrium` and docblocks from `RosterServiceProvider`; point `impersonation-banner` at the plain partial; strip Atrium wording from the listed domain/support files
- [x] 6.3 Remove the `viewAtrium` gate, `atrium.view` from `BuiltInRoles`, `'atrium.'` from `GLOBAL_ONLY_PREFIXES`; add `roster.users.approvals.review_url` (banner) and use it in the notification; drop the Atrium fallback from `ImpersonationWebController`; rewrite config banners mentioning Atrium and add the `routes.enabled` note about the lookup; remove Atrium strings from `lang/`
- [x] 6.4 Composer and workbench: drop `refactor-circus/atrium` from `require-dev`/`suggest`/`conflict` (and the VCS repository unless `composer update` needs it, D-016); `testbench.yaml` without the Atrium provider and `atrium-assets`, start `/`; remove the `without-atrium` CI job, `AtriumUnavailable` mode, `AtriumSeamTest`, the Atrium bits of `TestCase`/`AuthorizationTestCase` and the listed mixed tests
- [x] 6.5 Run vendor/bin/pest; all green

## Group 7: OpenAPI export and TypeScript client
depends on: 6
files: composer.json (dedoc/scramble, client:generate), workbench/app/Providers/WorkbenchServiceProvider.php, openapi.json, client/package.json, client/package-lock.json, client/tsconfig.json, client/src/index.ts, client/src/schema.d.ts, client/scripts/generate.mjs, .gitignore, .github/workflows/tests.yml (client job), tests/Feature/OpenApiTest.php
criteria: 21, 22, 23, 24

- [x] 7.1 Write `tests/Feature/OpenApiTest.php`: one operation per `roster.*` API route (operationId = route name, includes the six new routes and `roster.history.index`), none for web routes; fresh Scramble export equals committed `openapi.json`; `schema.d.ts` header sha256 equals the committed `openapi.json`'s; `client/package.json` name/version/deps/scripts as specified; `index.ts` exports `createRosterClient`
- [x] 7.2 Require `dedoc/scramble` (dev); configure it in the workbench (api path = roster prefix, route filter `roster.*` + `api` middleware, title "Roster API", version "2.0.0", operationId from route name), enable `roster.routes.enabled` there; export pretty-printed, key-sorted `openapi.json`
- [x] 7.3 Create `client/` (`@refactor-circus/roster-client` 2.0.0, openapi-fetch, openapi-typescript, typescript; `generate` writes `src/schema.d.ts` with the sha256 header; `typecheck` runs `tsc --noEmit`), `src/index.ts` with `createRosterClient({ baseUrl, fetch?, headers?, credentials? })` and `paths`/`components` re-exports; gitignore `client/node_modules`; commit generated files
- [x] 7.4 Add `composer client:generate` (Scramble export via testbench, then `npm --prefix client run generate`) and the CI `client` job (npm ci, generate, typecheck, `git diff --exit-code client/`)
- [x] 7.5 Run vendor/bin/pest; all green

## Group 8: Docs, changelog and standards
depends on: 7
files: README.md, CHANGELOG.md, resources/boost/skills/roster-development/SKILL.md, CLAUDE.md, AGENTS.md, docs/agent/standards/roster/atrium-views.md (delete), docs/agent/standards/roster/action-surfaces.md, docs/agent/standards/roster/optional-surfaces.md, docs/agent/standards/roster/testbench-environment.md, docs/agent/standards/http/authorization.md, docs/agent/standards-feedback.md, tests/Feature/DocsTest.php
criteria: 6, 25, 26, 27

- [x] 8.1 Update `DocsTest`: README has no "Atrium dashboard"/"Screen extensions" sections, documents the six endpoints, the status-to-route mapping, the lookup security model, `roster.routes.enabled` default false and the OpenAPI/client workflow; CHANGELOG has the v2.0.0 Breaking upgrade note and Added list
- [x] 8.2 Rewrite README (API + MCP, Atrium v2 as optional UI) and add the CHANGELOG v2.0.0 entry
- [x] 8.3 Update the Boost skill, `CLAUDE.md` and `AGENTS.md` (remove Atrium/Screens architecture bullets and `composer test:browser`; add new endpoints, regeneration); update the local standards (delete `roster/atrium-views`, rewrite `action-surfaces`, `optional-surfaces`, `testbench-environment`, `http/authorization`); mark the 2026-10-30 plain-views feedback row resolved
- [x] 8.4 Run vendor/bin/pest; all green

## Addendum 2026-11-23: roles and permissions move to Guardian

Builders: if a design question comes up, choose the most conservative option consistent with spec.md and decisions.md, record it as the next D-entry in decisions.md, and continue. You may change files outside a group's list when the group needs it; commit them with the group.

## Group 9: Scope models and declared abilities
depends on: 8
files: src/Domains/Organization/Models/OrganizationModel.php, src/Domains/Team/Models/TeamModel.php, src/Support/Authorization/RosterAbilities.php, src/RosterServiceProvider.php, config/roster.php, composer.json, tests/Unit/ScopeModelsTest.php, tests/Feature/RosterAbilitiesTest.php
criteria: 30, 31, 32

- [x] 9.1 Write tests: `ScopeModelsTest` (team's `parentScope()` is its organization; organization's is null; owner `grantsByItself` true for `roster.teams.manage`, false for `roster.users.view` and `roster.organizations.create`; non-owner false; team false); `RosterAbilitiesTest` (after boot Keystone `Abilities` has every former permission except `roster.roles.*` with its description; `grantTo` for `admin` and `member` as criterion 31); `roster.impersonation.blocked` default contains `*.roles.manage` and `*.roles.assign` and no `roster.roles.*`
- [ ] 9.2 Run `composer update refactor-circus/keystone` so the Keystone authorization addendum is installed (stop and report if it is not on Keystone `dev-main` yet)
- [x] 9.3 Implement `Scope` on both models and move the global-only list to `OrganizationModel::GLOBAL_ONLY_ABILITIES` (keep `Permissions::GLOBAL_ONLY_PREFIXES` delegating to it until group 13)
- [x] 9.4 Add `RosterAbilities` and define them from `RosterServiceProvider::boot()`; change the blocked defaults in `config/roster.php`
- [x] 9.5 Run vendor/bin/pest; all green

## Group 10: Access checks through Keystone's contract
depends on: 9
files: src/Support/Authorization/Authorizer.php, src/Support/Authorization/Resources.php, src/Support/Authorization/ImpersonationAwarePermissions.php, src/Domains/Permission/Services/RosterRolesPermissions.php, src/Domains/Permission/PermissionServiceProvider.php, src/Domains/Permission/Services/Authorizer.php (delete), src/Domains/Permission/Services/Resources.php (delete), src/Support/Policies/Policy.php, src/Support/Concerns/AuthorizesWithRoster.php, src/Domains/Transfer/Services/Transfers.php, src/Domains/Transfer/Http/Requests/ShowImportTemplateRequest.php, src/Domains/Transfer/Mcp/Requests/ShowImportTemplateMcpRequest.php, src/Domains/Transfer/Actions/ConfirmImportAction.php, src/RosterServiceProvider.php, tests/Feature/AccessThroughContractTest.php
criteria: 33, 34, 38

- [ ] 10.1 Write tests in `AccessThroughContractTest`: with Keystone's `FakePermissions` installed, `Authorizer::permits()` and `check()` follow the fake (grant in an organization, parent organization for a team scope, the owner rule through `Scope`); `organizationsWith()` combines `scopesWith()` with owned organizations, keeps a null from `scopesWith()`, and drops owned organizations for a global-only ability; while impersonating, `allows('acme.roles.manage')` on a fake bound after boot is false; `Gate::before` answers true or null for `roster.*` abilities with the context scope and leaves super-admin passing to the implementation
- [ ] 10.2 Move `Authorizer` and `Resources` to `src/Support/Authorization/` (drop the `roles` core resource); `permits()` and `organizationsWith()` use the contract as criterion 33; update every import (Policy, AuthorizesWithRoster, Transfer classes, providers)
- [ ] 10.3 Add `ImpersonationAwarePermissions` and register it with `$this->app->extend(Permissions::class, ...)` in `RosterServiceProvider::register()`
- [ ] 10.4 Add the interim `RosterRolesPermissions` (Roster's current `Permissions` service behind Keystone's `Permissions` and `Roles`) and bind it in `PermissionServiceProvider` with `scoped()`; rewrite the `Gate::before` as criterion 38
- [ ] 10.5 Run vendor/bin/pest; all green (the existing role-based tests pass through the adapter)

## Group 11: Authorization suite on Keystone's fakes
depends on: 10
files: tests/AuthorizationTestCase.php, tests/Authorization/fixtures.php, tests/Authorization/*.php, tests/Pest.php
criteria: 40

- [ ] 11.1 Rewrite `tests/Authorization/fixtures.php` and `AuthorizationTestCase` to install `FakePermissions` and `FakeRoles`, define `super-admin`, `admin` and `member` from `RosterAbilities`'s `grantTo`, and grant through `FakeRoles::assign()` in place of `RoleModel`/`RoleAssignmentModel` rows
- [ ] 11.2 Port each file in `tests/Authorization/` to those fixtures with unchanged expectations, and mark the cases that test Roster's role API itself (`RoleActionsTest`, `PermissionsTest`, `OrganizationRoleAssignmentsAuthorizationTest`, role/permission routes in `HttpAuthorizationTest` and `McpAuthorizationTest`) for deletion in group 13 by moving them to `tests/Authorization/Legacy/`
- [ ] 11.3 Run vendor/bin/pest; all green

## Group 12: Role writes and reads through Keystone's Roles
depends on: 11
files: src/Domains/Organization/Concerns/ManagesMemberships.php, src/Domains/User/Actions/DeleteUserAction.php, src/Domains/User/Actions/PurgeUserAction.php, src/Domains/User/Services/Approvals.php, src/Domains/User/Concerns/HasRoster.php, src/Domains/User/UserServiceProvider.php, src/Domains/Transfer/Services/Exporters/MembersExporter.php, src/Domains/Transfer/Services/Planners/MembersPlanner.php, src/Domains/Impersonation/Actions/StartImpersonationAction.php, src/Roster.php, tests/Feature/RolesThroughContractTest.php
criteria: 35, 36, 37

- [ ] 12.1 Write tests in `RolesThroughContractTest` with `FakeRoles`/`FakePermissions`: joining assigns `default_member` in the organization scope (none when null or unknown); leaving an organization revokes in it and its teams; leaving a team revokes in the team; delete and purge call `revokeEverywhere`; `forget()` is called after each change; approvers come from `holders('roster.users.approve')`; the export role column and the import's unknown-role error and assignment; the impersonation super-admin and escalation rules, including null `abilities()`; `externalSignInFor()` with a super-admin; `hasRosterPermission()` asks the contract; no `rosterRoleAssignments` relation
- [ ] 12.2 Switch `ManagesMemberships`, `DeleteUserAction`, `PurgeUserAction` and ownership transfer to `Roles`/`Permissions::forget()`
- [ ] 12.3 Switch `Approvals`, `MembersExporter`, `MembersPlanner` (drop `AssignRoleAction`), `StartImpersonationAction` and `Roster::externalSignInFor()`
- [ ] 12.4 Remove `rosterRoleAssignments()` from `HasRoster` and its relation from `UserServiceProvider`; `hasRosterPermission()` asks the contract
- [ ] 12.5 Run vendor/bin/pest; all green

## Group 13: Delete the Role and Permission domains
depends on: 12
files: src/Domains/Role/** (delete), src/Domains/Permission/** (delete), src/Domains/DomainServiceProvider.php, src/Mcp/RosterServer.php, src/Support/Audit.php, src/Support/Tables.php, config/roster.php, database/migrations/2026_01_01_000003_create_roster_role_tables.php (delete), database/migrations/2026_01_01_000010_add_roster_lookup_indexes.php, database/migrations/2026_10_12_000001_add_roster_performance_indexes.php, tests/Feature/Permission/** (delete), tests/Authorization/Legacy/** (delete), tests/Feature/Http/*Role*, tests/Feature/Mcp/*Role*, tests/Feature/Mcp/*Permission*, tests/Feature/MigrationsRoleTablesTest.php, tests/ArchTest.php, tests/Feature/ComposerTest.php, tests/Pest.php
criteria: 28, 29, 30, 33, 41

- [ ] 13.1 Write tests: routes and MCP tools absent (criterion 28); commands absent; a fresh migrate creates no role tables; with pre-created `roster_*` role tables holding rows, migrate and rollback leave them and their rows; config keys gone (criterion 30); arch: nothing uses `Domains\Role` or `Domains\Permission`; no `guardian` string in `src/`, `config/` or `database/`; `composer.json` names no guardian package
- [ ] 13.2 Delete both domains (including the interim adapter and group 1's role-assignments endpoint), and remove them from `DomainServiceProvider`, `RosterServer::TOOLS`, `Support/Audit` and the parity exemptions
- [ ] 13.3 Delete the role-tables migration and remove `roles`/`role_assignments` from the two index migrations; remove `permission_cache`, `super_admins`, the role policies and table keys from `config/roster.php` and `Support/Tables`
- [ ] 13.4 Delete the tests of removed code (`tests/Feature/Permission/`, `tests/Authorization/Legacy/`, role/permission HTTP and MCP tests) and update the parity list in `tests/Pest.php`
- [ ] 13.5 Run composer analyse and vendor/bin/pest; all green

## Group 14: Gate fallback coverage
depends on: 13
files: tests/Feature/GateFallbackTest.php, tests/TestCase.php
criteria: 39

- [ ] 14.1 Write `GateFallbackTest` on Keystone's defaults: boot, migrate, the API answering; the owner may update their organization; `Gate::define('roster.users.view', ...)` lets a user list users; a user with neither gets 403; joining assigns nothing without failing; awaiting approval notifies nobody without failing; an import role is a row error; only a host-defined `super-admin` may impersonate
- [ ] 14.2 Make `tests/TestCase.php` assert in a `setUp` guard that the bound implementations are Keystone's `GatePermissions` and `NullRoles`; fix anything the new tests expose
- [ ] 14.3 Run vendor/bin/pest; all green

## Group 15: Regenerate OpenAPI and the client
depends on: 14
files: openapi.json, client/src/schema.d.ts, client/dist/*, tests/Feature/OpenApiTest.php
criteria: 42

- [ ] 15.1 Update `OpenApiTest`'s expected operations: drop every route removed in criterion 28, and assert none of them is in the document
- [ ] 15.2 Run `composer client:generate` and commit `openapi.json` and the regenerated client
- [ ] 15.3 Run vendor/bin/pest; all green

## Group 16: Docs, changelog and standards for the move
depends on: 15
files: README.md, CHANGELOG.md, resources/boost/skills/roster-development/SKILL.md, CLAUDE.md, AGENTS.md, docs/agent/standards/**, docs/agent/standards-feedback.md, tests/Feature/DocsTest.php
criteria: 43, 44, 45, 46

- [ ] 16.1 Update `DocsTest`: the README "Roles and permissions" section names Keystone's authorization contract, the Gate fallback with a `Gate::define` example and `super-admin`, and the owner rule. None of README, Boost skill, `CLAUDE.md`, `AGENTS.md` or `docs/agent/standards/` contains `PermissionCatalogue`, `roster:sync-permissions`, `permission_cache` or `rosterRoleAssignments`. CHANGELOG v2.0.0 Breaking names the removed routes, tools, commands and config, and its upgrade note names `refactor-circus/guardian` `^2.0` and dropping the `roster_*` role tables by hand.
- [ ] 16.2 Rewrite the README section (drop the built-in roles table, `permission_cache` and the "Extending Roster" permission registration, and fix the global-only list per D-022), and extend the CHANGELOG
- [ ] 16.3 Update the Boost skill, `CLAUDE.md`, `AGENTS.md` and the local standards that describe roles or permissions. Add a `standards-feedback.md` row if any standard still assumes Roster owns permissions.
- [ ] 16.4 Run composer test; all green
