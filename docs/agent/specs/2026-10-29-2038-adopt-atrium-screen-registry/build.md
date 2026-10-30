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
