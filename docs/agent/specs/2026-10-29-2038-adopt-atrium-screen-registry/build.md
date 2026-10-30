# Build

## Group 1

Remove other packages' UI from Roster screens (criteria 7, 8, 11).

### Files

- Deleted: `src/Atrium/RedirectDomains.php`, `src/Atrium/Http/Controllers/RedirectDomainUiController.php`, `resources/views/ui/partials/redirect-domains.blade.php`, `tests/Cortex/RedirectDomainsTest.php`, `tests/Feature/Ui/RedirectDomainPagesTest.php`, `tests/Feature/Ui/AuditTrailTest.php`, `tests/Feature/HistoryNavigationTest.php`.
- `src/Atrium/Http/Controllers/OrganizationUiController.php`: `offers()` removed; `mcp` and `activity` dropped from the permission map; the visible built-ins are now read from that map (not `Tabs::ORGANIZATION_TABS`, which still lists both until Group 2 deletes it); no `ListRedirectDomainsAction` or `Roster::auditing()` use.
- `src/Atrium/Http/Controllers/UserUiController.php`: no `redirectDomains` overview data, no `ListRedirectDomainsAction`.
- `src/Atrium/RosterPlugin.php`: the four `*.redirect-domains.store|destroy` routes and the `historyNavItem()` call removed.
- `resources/views/ui/organizations/show.blade.php`: `mcp`/`activity` branches and icons removed.
- `resources/views/ui/users/partials/overview.blade.php`, `ui/roles/show.blade.php`, `ui/teams/show.blade.php`: `<x-atrium::audit-trail>` removed; the redirect-domains card removed from the overview.
- `resources/views/ui/users/show.blade.php` (outside the group's file list, see Deviations): stops passing `redirectDomains` to the overview partial.
- `lang/en/roster.php`: `mcp`, `redirect_domains`, `redirect_domains_hint`, `no_redirect_domains` removed.

### Tests

- `tests/Modes/Keen/OrganizationActivityRemovedTest.php` (new): with Keen installed and Roster auditing, no `tab-activity`; `?tab=activity` answers 200 on the default (members) tab.
- `tests/Feature/Ui/RemovedIntegrationsTest.php` (new): `Route::has()` false for the four redirect-domain routes; the four translation keys are gone from `lang/en/roster.php`; `RosterPlugin::navigation()` has no item whose route starts with `atrium.history`; `?tab=mcp` answers 200 with no `tab-mcp`.
- `tests/ArchTest.php`: `RefactorCircus\Cortex` not used in `RefactorCircus\Roster`; file-content scans for `redirect-domains`/`RedirectDomain` and `historyNavItem`/`audit-trail` in `src` and `resources`, and `x-atrium::audit` in `resources/views` (shared helper `rosterFilesContaining()`).
- `tests/Authorization/ScreenControlsTest.php` (forced change): "lists only the organization tabs the viewer may open" drops its `tab-activity` assertion and `?tab=activity` now expects 200; "shows a record's history only to those who may read the audit log" deleted.
- Not run here: the gate runs `vendor/bin/pest` after the build.

### Deviations

- Touched `resources/views/ui/users/show.blade.php`, which Group 2 owns: it passed `$redirectDomains` to the overview partial, so removing the controller data without this one-line change would break every user page.
- `lang/en/roster.php` keeps `redirect_domain_input_hint`, `domain`, `add_domain`, `redirect_domain_added` and `redirect_domain_removed`, now unused: the spec removes exactly the four listed keys.
- `docs/agent/standards/roster/atrium-views.md` ("Audit history") and the package `CLAUDE.md` still describe `<x-atrium::audit-trail>` and `RedirectDomains`; `CLAUDE.md` is Group 4's, and the local standard's "Audit history" section should be dropped alongside it.
- `tests/Feature/PerformanceTest.php` still lists `activity` in its organization-tab dataset; it now renders the default tab and stays within its query bound, so it was left as is.

## Group 2 (salvaged)

Declare every screen on Atrium's registry (criteria 1 to 6, 9, 10). The first build agent ended without a result; this salvage finished and committed its work.

### What was found

- No index lock, merge or rebase.
- Done but uncommitted: the deletions (`Tabs`, `TabDefinition`, `TabVisibility`, both partials, the three unit tests) staged; the `Tabs`/`TabVisibility` bindings gone from `RosterServiceProvider`; `ScreenAccess::allowsOn()`; the `screen()` and `overviewTab()` helpers on `AuthorizesScreens`; all 13 controller actions of the criterion 3 table building `Screen::standard()`, resolving after their own authorization, choosing data by `$resolved->openTab` and passing `resolved`; all 13 views rendering `x-atrium::screen-tabs`, `screen-tab-panel` and every declared slot as Design describes; `tests/Pest.php` applying `ResetsScreenExtensions` behind `trait_exists()`; the `ArchTest` absence and scan tests; `ScreenDeclarationsTest` and both `ScreenRegistryResetTest`s.
- The list eager loads (`organization` on roles and impersonations, `organization` and `requester` on transfers) were already in the list Actions; nothing to change.
- Not done: `ScreenAccessOnTest`, `ScreenOrderOfEvaluationTest`, and the rewrite of the five PR #5 tests, which still used the deleted classes.

### What this salvage added

- `tests/Feature/Ui/ScreenAccessOnTest.php` (new): one allowed and one denied case per record row of criterion 4 (organization, team, user, role in an organization, global role, impersonation in an organization, global impersonation, transfer, invitation, no record); a transfer's requester asked as `self` and a null requester taking the organization-only check; an unknown model class refused; a `permission('roster.members.view')` `aside` section on `roster.organization` shown only to a viewer holding it there; a `permission('roster.users.update')` `row.actions` section on `roster.users` shown only in the rows the viewer may update; query counting on the roles, impersonations and transfers lists (the per-row cost of a `row.actions` section is the same with one row as with three).
- `tests/Feature/Ui/ScreenOrderOfEvaluationTest.php` (new): the refused-viewer dataset over the seven detail screens (403, counting `authorize` and resolver closures on a tab and an `aside` section run zero times, with and without `?tab=`), its admitted-viewer control, and the deleted organization and user pages (200 for `?tab=test.tab`, zero runs).
- Rewritten to register through `Atrium::screens()->tab('roster.organization' | 'roster.user', TabDefinition::make(...))` with Atrium's `TabDefinition`: `OrganizationTabBarTest` (plus links keeping the query string except `page`, and no bar for a viewer with one built-in tab), `OrganizationRegisteredTabsTest` (plus `?tab=activity`/`?tab=mcp` on the default tab, a built-in the viewer may not open answering 403, a registered `members` tab skipped and reported with one `tab-members`, and only the open built-in tab's tables read, none while a registered tab is open), `UserRegisteredTabsTest`, `SharedTabBarTest` and `UserPagesTest` (its update redirect has no `tab=`). Every criterion 10 case is kept; where Atrium differs (view gets `screen`, `record`, `tab`; a component gets only `data`; the bar is absent below two tabs) the tests assert Atrium's behaviour. `tab`/`tabs` view variables are now read from `resolved`.
- `tests/Pest.php`: import order fixed for Pint.

### Deviations

- `phpunit.xml.dist` (outside the group's files): its `Unit` suite is removed. The spec deletes the suite's only three files, and PHPUnit throws `TestDirectoryNotFoundException` for a configured directory that does not exist, which would fail every run.
- The `ArchTest` scan for the old registry's names covers `src`, `resources` and `tests` but leaves out `resources/boost` and does not read `README.md`: both still describe "Registered tabs" until Group 4 rewrites them. Group 4 should drop the `resources/boost` exclusion and add `README.md` to the scan when it does.
- The organization screen does not call `Screen::reserve()` yet: criterion 6's reserved-key case for keys the viewer may not open is Group 5. Atrium's `Screen::reserve()` is already present in the linked Atrium checkout.

### Left

- 2.3: run `vendor/bin/pest` and `composer test:browser` (not run here; the gate runs the suite).

## Group 2

Retry that finishes task 2.3 on top of the salvaged commit (`e10f7f0`).

### Files

- `docs/agent/specs/2026-10-29-2038-adopt-atrium-screen-registry/tasks.md`: 2.3 ticked.
- No code changes. The salvaged work above stays as is. A check of `src`, `resources/views` and `tests` found the deleted `Tabs`, `TabDefinition`, `TabVisibility`, `partials.tabs` and `registered-tab` named only in `tests/ArchTest.php`'s absence and scan tests.

### Tests

- 2.3 (`vendor/bin/pest`, `composer test:browser`): not run here, as instructed; the gate runs the suite after the build.

### Deviations

- None beyond those listed in "Group 2 (salvaged)". `composer.json` and `composer.local.json` were not touched: the Atrium move and its `<0.1.0` conflict belong to Group 3, and `composer.local.json` must be edited by hand, never regenerated.
- `AGENTS.md` has an uncommitted change from a Ringside sync (the "Keep this checkout on `main`" line). It was left out of this commit.

## Group 3 (salvaged)

### Found

No index lock and no merge or rebase in progress. The interrupted agent had left all of 3.1 and 3.2 uncommitted in the worktree:

- `composer.json`: Atrium moved from `require` to `require-dev` (`dev-main`) and `suggest`, with the three suggest strings from criterion 12 and `"conflict": {"refactor-circus/atrium": "<0.1.0"}`. `composer.local.json` (gitignored) has the same change, made by hand; it still points `refactor-circus/atrium` at `../Atrium--2026-10-29-2037-screen-extensions-registry` aliased `dev-main`.
- `Support\Users::STATUS_COUNTS_CACHE`. `RosterPlugin::STATUS_COUNTS_CACHE` is now set to it, and the provider's cache listener uses it.
- `Atrium\AtriumServiceProvider`. It binds the Atrium `InvitationPage`, registers `@rosterCan` and shares `rosterUsesAtrium`. `RosterServiceProvider` registers it after the domains, only when `! self::$withoutAtrium && class_exists('RefactorCircus\Atrium\Atrium')`. `$withoutAtrium` is a new `@internal` public static bool. `@rosterCan` and its `ScreenAccess` import left `RosterServiceProvider`.
- `Domains\Invitation\Contracts\InvitationPage`, `Services\PlainInvitationPage` (bound in `InvitationServiceProvider::register()`, with the shared view data) and `Atrium\InvitationPage`, which declares `roster.invitation` as a detail screen with the `overview` tab. `InvitationWebController::show()` delegates to the bound page.
- Views: `invitations/show` is now plain HTML (`data-testid="invitation-plain"`, inline styles). The Atrium markup moved to `ui/invitations/show`, which adds the screen's tabs, panel and slots. `impersonation-banner` is now a dispatcher. Its Atrium markup moved to `ui/impersonation-banner`, and the plain banner is in `partials/impersonation-banner-plain`.
- Tests: `ComposerTest`, `AtriumSeamTest`, `tests/Modes/AtriumUnavailable/AtriumUnavailableTest.php` with `AtriumUnavailableTestCase` and its `Pest.php` mapping, the three arch rules plus the `RefactorCircus\Atrium` string scan in `ArchTest`, `StylesTest` excluding exactly the two plain views, and the markup assertions in `InvitationPageTest` and `ImpersonationFlowTest`.
- `.github/workflows/tests.yml`: a `without-atrium` job. It removes Atrium, Cortex and Impex, runs `composer update --no-scripts`, checks that `RefactorCircus\Atrium\Atrium` does not exist, and runs `vendor/bin/pest tests/Modes/AtriumUnavailable`. I checked Keen's requirements: Keen, like PennantPlus, has Atrium only in `require-dev`, so it stays.
- `docs/agent/standards-feedback.md`: a `roster/atrium-views` row for the two plain views.

### Fixed in this salvage

- `tests/Pest.php` was missing the `use RefactorCircus\Roster\Tests\AtriumUnavailableTestCase;` import, so the mapping named a global class that does not exist.
- `tests/ArchTest.php`: the rule "only Roster's Atrium folder uses Atrium" failed locally. Local installs symlink the sibling packages, so Pest's arch plugin treats PennantPlus (and Cortex, Impex, Keen) as project code. It now `->ignoring()`s those four namespaces. The rule itself is unchanged.
- `src/RosterServiceProvider.php`: reflowed one overlong docblock line.

### Tests

- Ran only this group's files (`ComposerTest`, `AtriumSeamTest`, `tests/Modes/AtriumUnavailable`, `StylesTest`, `tests/Feature/Web`, `ArchTest`): all pass, 60 tests. `composer analyse` and Pint on the touched files also pass.
- 3.3 (the full `vendor/bin/pest`) was not run here, as instructed; the gate runs it.

### Left

- 3.3: run the full `vendor/bin/pest`.
- The `without-atrium` CI job can only be proven green on the PR (criterion 15).

## Group 3

This retry finishes task 3.3 on top of the salvaged commit (`bb8e60a`).

### Files

- `docs/agent/specs/2026-10-29-2038-adopt-atrium-screen-registry/tasks.md`: 3.3 ticked.
- No code changes. The salvaged commit already holds every file from 3.1 and 3.2: `composer.json`, the workflow job, `AtriumServiceProvider`, both `InvitationPage`s and the contract, the split views, `Users::STATUS_COUNTS_CACHE`, the standards-feedback row and all of the group's tests.

### Tests

- 3.3 (`vendor/bin/pest`): not run here, as instructed. The gate runs the suite after the build.

### Deviations

- None beyond those listed in "Group 3 (salvaged)".
- `AGENTS.md` still has an uncommitted change from a Ringside sync. It was left out of this commit.

## Group 4

Documentation and merge gate (criteria 18 to 21).

### Files

- `README.md`: "Registered tabs" replaced by "Screen extensions" (under "Atrium dashboard"): a registration example with `Atrium::screens()->tab('roster.organization', …)`, `->section('roster.user', 'aside', …)` and a `row.actions` section; the table of the 13 screen keys of criterion 3 plus `roster.invitation`, with page, kind, record and slots; the built-in tabs (`overview`; the organization's seven, all reserved for every viewer); the `ScreenAccess::allowsOn()` mapping of criterion 4; rendering, testing (`ResetsScreenExtensions`) and re-publish notes; a pointer to Atrium's README. "MCP redirect domains" removed (one line under "Cortex" says Cortex adds its own screens). Installation says Atrium is optional (`composer require refactor-circus/atrium`, v0.1.0 or later, `atrium.roster.*` routes absent without it). The intro, invitation, banner, "Reading it" and "Atrium dashboard" text no longer promise history, activity tabs or Atrium-only views. "Testing" gives the without-Atrium lane's commands in a scratch worktree (criterion 15's proof).
- `resources/boost/skills/roster-development/SKILL.md`: "13. Registered tabs" replaced by "13. Screen extensions" with the same facts; Atrium optional in "Install and configure"; auditing, banner, Atrium rule and the `Tabs` anti-pattern updated.
- `CLAUDE.md` (a symlink; the edit is in `AGENTS.md`, its target): the `audit-trail` and `RedirectDomains` lines are gone; new lines say Atrium is optional (the seam, `$withoutAtrium`, the conflict, the AtriumUnavailable mode) and that screens declare themselves with `Screen::standard()` through `AuthorizesScreens::screen()` and `ScreenAccess::allowsOn()`; the plain-view exception is named.
- `CHANGELOG.md`: the Unreleased "Breaking" section opens with the upgrade note of criterion 19: "Roster no longer requires `refactor-circus/atrium`; require it to keep the dashboard", the `atrium.roster.*` routes, the removed `Tabs`/`TabDefinition`/`TabVisibility` and partials with how to re-register on `roster.organization`/`roster.user`, changed view variables and closure signatures, reserved keys (D-031) and duplicates, the removed `activity`/`mcp` tabs (`?tab=activity`, `?tab=mcp` open the default tab; Keen and Cortex take over), the removed redirect-domain routes, class, controller, card and translation keys, the removed audit trails and History nav item, the `roster-views` re-publish note, the `conflict` `<0.1.0`, and `@rosterCan` now Atrium-only. In "Added", the "Registered tabs" entry is replaced by a "Screen extensions" one, and the "MCP redirect domains" and "Audit log link" entries, which never ship, are removed; the older audit-trail entry under the second "Breaking" notes it was removed again.
- `docs/agent/specs/2026-10-29-2038-adopt-atrium-screen-registry/meta.yml`: `blockers:` names the four blockers of criterion 21 with their PR and status on 2026-10-30: Atrium PR #16 (open); Atrium `Screen::reserve()` (on the PR #16 branch, commit `797d3e3`, open); Cortex `register-roster-redirect-domains` (building on its job branch, no PR yet); Keen `history-section-by-capability` (Keen PR #8, open).
- `tests/Feature/DocsTest.php`, `tests/ArchTest.php` (see Tests).
- PR description: drafted outside the spec folder (the factory's scratchpad, returned with the result). It lists the four blockers with their status and every changed test by file and test name (deleted, changed, replaced and added).

### Tests

- `tests/Feature/DocsTest.php`: "documents registered tabs in the README and the Boost skill" replaced by "documents screen extensions in the README and the Boost skill" (both files: a "Screen extensions" heading and no "Registered tabs" one; every screen key, the built-in tab keys, the five slots, `ScreenAccess::allowsOn` and each record class of the mapping with `->organization`/`->requester`, `Atrium::screens()`, `->tab('roster.organization'`, `->section('roster.user', 'aside'`, `Screen::standard()`). Added "says in the README that Atrium is optional and drops the MCP redirect domains" (Installation, no "MCP redirect domains" heading, the without-Atrium commands in "Testing") and "writes the upgrade note in the Unreleased Breaking section of the CHANGELOG" (the opening sentence, `atrium.roster.`, `Tabs`, `?tab=activity`, `?tab=mcp`, `refactor-circus/atrium`, `conflict`, `<0.1.0`, `roster-views`). The section helper's own test now uses a "Screen extensions" fixture.
- `tests/ArchTest.php`: "nothing names Roster's old tab registry" now also scans `README.md` and no longer leaves out `resources/boost` (Group 2 left both to this group); `rosterFilesContaining()` accepts a file as well as a folder.
- Ran `tests/Feature/DocsTest.php` (7 passed) and `tests/ArchTest.php` (27 passed), and Pint on both files. 4.3 (the full `vendor/bin/pest` and `composer test:browser`) was not run here, as instructed; the gate runs them.

### Deviations

- `tests/ArchTest.php` is outside this group's files: criterion 1's scan must cover the README and the Boost skill, which only this group's rewrite makes possible.
- `CLAUDE.md` is a symlink to `AGENTS.md`, so `AGENTS.md` carries the change. Its uncommitted Ringside sync line ("Keep this checkout on `main`") was again left out of the commit.
- The docs describe criterion 6's reserved keys (all seven organization keys reserved for every viewer) as the release will have them; the `reserve(...)` call itself is Group 5, built once the blocker lands.
- RingMaster's own record of the blockers (criterion 21 says "`meta.yml` and RingMaster") needs RingMaster's MCP tools, which this session does not have; `meta.yml` holds them.
- `docs/agent/standards/roster/atrium-views.md` still has its "Audit history" section (`<x-atrium::audit-trail>`); it is not in this group's files (noted by Group 1 too).

## Group 4 (retry)

This retry checks the committed group 4 work (`aeb9e46`) and redrafts the PR description.

### Files

- No code or doc changes. `README.md`, the Boost skill, `CLAUDE.md` (`AGENTS.md`), `CHANGELOG.md`, `meta.yml` and `tests/Feature/DocsTest.php` already meet criteria 18, 19 and 21, and 4.1 to 4.3 are already ticked.
- PR description: redrafted outside the spec folder, in the factory's scratchpad, and returned with the result. The earlier draft was in a previous session's scratchpad. It lists the four blockers with their status, and every deleted, changed, rewritten and added test by file and test name.

### Tests

- Ran `tests/Feature/DocsTest.php` and `tests/ArchTest.php`: 34 passed. The full `vendor/bin/pest` and `composer test:browser` were not run here, as instructed; the gate runs them.

### Deviations

- None beyond those listed under "Group 4". `AGENTS.md`'s uncommitted Ringside sync line is still left out of the commit.

## Group 5

### Files

- `src/Atrium/Http/Controllers/OrganizationUiController.php`: `show()` now calls `->reserve(...array_keys(self::TAB_PERMISSIONS))` on `roster.organization`, so all seven built-in keys are reserved for every viewer (D-031), whatever tabs that viewer may open. It uses Atrium's `Screen::reserve(string ...$keys): static` from `src/Domains/Screens/Data/Screen.php` (commit 797d3e3, in the linked Atrium PR branch).
- `tests/Feature/Ui/OrganizationRegisteredTabsTest.php`: two new tests.
  - "skips and reports a registered tab named like a built-in the viewer may not open, refusing ?tab= for it": a viewer holding `roster.organizations.view` and `roster.members.view` sees no `tab-roles` for a `public()` registered `roles` tab, and the skip is reported once (`Exceptions::fake()`, `assertReportedCount(1)`, message names `[roles]`). `?tab=roles` answers 403, and the resolver never runs.
  - "reserves every built-in key for a viewer who may open only settings": a dataset over `members`, `teams`, `invitations`, `roles`, `sso` and `scim`. Each one is reserved, absent from the bar and reported once. `settings` is covered by the existing "named like a built-in it is given" test.

### Tests

- Pint and PHPStan pass on both files. As instructed, the full suite was not run here; the gate runs it.
- A run of `tests/Feature/Ui/OrganizationRegisteredTabsTest.php` alone failed for a reason outside this group: 41 of 44 tests answered 500 when the Atrium layout rendered `Call to undefined method RefactorCircus\Impex\Atrium\ImpexPlugin::historyNavItem()`. `vendor/refactor-circus/impex` links to `../../../Impex/` (main), which still calls `historyNavItem()`. The linked Atrium branch removed it, and the Impex job worktree (`Impex--2026-10-29-2038-register-showroom-run-link`) no longer calls it. The vendor links are factory state, so this group left them unchanged.

### Deviations

- None in code. `AGENTS.md`'s uncommitted Ringside sync line is still left out of the commit.

## Group 5 (retry)

This retry checks the committed group 5 work (`a2115b6`). The `reserve(...)` call and both reserved-key tests stay as they were.

### Files

- `tests/Feature/Ui/OrganizationRegisteredTabsTest.php`: updated "lets a throwing resolver propagate". Atrium runs the open tab's resolver while the panel renders (`ResolvedScreen::data()` in the `screen-tab-panel` component), so the resolver's `RuntimeException` arrives wrapped in nested `Illuminate\View\ViewException`s rather than bare. The test now asserts a 500 with exception handling on. With exception handling off, it asserts that a `ViewException` is thrown and that the root of its chain is the `RuntimeException('resolver broke')`. Criterion 10's case ("a throwing resolver on the open tab propagates (500)") still holds.
- `tests/Feature/Ui/UserRegisteredTabsTest.php` (outside this group's files): the same fix for the user screen's test of the same name. It failed for the same reason, and the gate needs it green.

### Tests

- With `vendor/refactor-circus/impex` and `vendor/refactor-circus/cortex` pointed at their job worktrees (`Impex--2026-10-29-2038-register-showroom-run-link`, `Cortex--2026-10-29-2038-register-roster-redirect-domains`) for the run: `OrganizationRegisteredTabsTest.php` and `UserRegisteredTabsTest.php` pass, 100 tests and 573 assertions. That includes both reserved-key tests. Pint passes on both files. The links were then restored to `../../../Impex/` and `../../../Cortex/`.
- With the factory's links (`Impex` and `Cortex` on `main`), every Atrium page still answers 500. Both `main` checkouts call `historyNavItem()`, which the linked Atrium branch removed. This is environment state, not Roster code. The full suite was not run here, as instructed.

### Deviations

- `tests/Feature/Ui/UserRegisteredTabsTest.php` was touched outside the group's file list (see above).
