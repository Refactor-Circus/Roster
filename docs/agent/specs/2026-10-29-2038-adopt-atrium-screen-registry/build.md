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
