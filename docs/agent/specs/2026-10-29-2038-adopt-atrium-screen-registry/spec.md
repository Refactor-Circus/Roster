# Adopt Atrium screen registry

## Goal

Every Roster dashboard list and detail screen in the criterion 3 table declares itself to Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so any package can add tabs and sections to it (screens outside the table are listed under "Not declared" and Out of scope), and Roster stops rendering other packages' UI. Roster's own tab registry from PR #5 is deleted and its behaviour carries over through Atrium's. Atrium becomes a suggested package: Roster boots, and serves its HTTP API, MCP server, invitation page and impersonation banner, without it (D-027). An Atrium older than the screen registry is refused by Composer (D-030). The work ships as one spec and one PR (D-028).

## Acceptance criteria

### Roster's tab registry is gone

1. **Deleted.** These are removed: `src/Atrium/Tabs.php`, `src/Atrium/TabDefinition.php`, `src/Atrium/TabVisibility.php`, `resources/views/ui/partials/tabs.blade.php` and `resources/views/ui/partials/registered-tab.blade.php`, plus the `Tabs` singleton and `TabVisibility` scoped bindings in `RosterServiceProvider::register()`. The registry's own unit tests go with them: `tests/Unit/TabDefinitionTest.php`, `tests/Unit/TabsTest.php` and `tests/Unit/OrganizationTabsTest.php`. Atrium's tests own that behaviour now.
   - **Proof:** a test in `tests/ArchTest.php` asserts that `class_exists()` is false for the three classes and `view()->exists()` is false for both partials.
   - **Absence scan:** a grep for `Atrium\Tabs`, `TabDefinition`, `TabVisibility`, `partials.tabs` and `registered-tab` over `src`, `resources`, `tests`, `README.md` and the Boost skill finds nothing.
   - **Proof (done-step):** a file-content scan test in `tests/ArchTest.php` over those paths (excluding `CHANGELOG.md`, which is not searched) asserts none of those strings appears.
2. **Test reset.** `tests/Pest.php` stops flushing `Tabs`. Instead it applies Atrium's `RefactorCircus\Atrium\Testing\ResetsScreenExtensions` to `Feature` and `Authorization`. That `uses()` call only runs when `trait_exists(ResetsScreenExtensions::class)`, so `Pest.php` still loads without Atrium (criterion 17).
   - **Proof:** order-independent. A test in `tests/Feature` and one in `tests/Authorization` each register a tab on `roster.organization` in a `beforeEach`, run the trait's reset hook directly (`afterEach`-equivalent call), and assert `Atrium::screens()` holds no registration. No test depends on another having run.

### Every screen declares itself

3. **Declared screens.** Each controller action below builds `Screen::standard($key, $kind)` with the record and access closure from criterion 4. It calls `Atrium::screens()->resolve($screen, $request)` only after its own `authorizeScreen()`/`authorizeList()` has passed, and passes the result to its view as `resolved`.

   | Key | Controller action | Kind | Record | Built-in tabs |
   |---|---|---|---|---|
   | `roster.users` | `UserUiController::index` | list | none; `row.actions` per user row | `overview` |
   | `roster.user-create` | `UserUiController::create` | detail | none | `overview` |
   | `roster.user` | `UserUiController::show` | detail | the user | `overview` (criterion 9) |
   | `roster.organizations` | `OrganizationUiController::index` | list | none; `row.actions` per organization row | `overview` |
   | `roster.organization-create` | `OrganizationUiController::create` | detail | none | `overview` |
   | `roster.organization` | `OrganizationUiController::show` | detail | the organization | criterion 6 |
   | `roster.team` | `TeamUiController::show` | detail | the team | `overview` |
   | `roster.roles` | `RoleUiController::index` | list | none; `row.actions` per role row | `overview` |
   | `roster.role` | `RoleUiController::show` | detail | the role | `overview` |
   | `roster.permissions` | `PermissionUiController::index` | list | none; `row.actions` per permission row | `overview` |
   | `roster.impersonations` | `ImpersonationUiController::index` | list | none; `row.actions` per impersonation row | `overview` |
   | `roster.transfers` | `TransferUiController::index` | list | none; `row.actions` per transfer row | `overview` |
   | `roster.transfer` | `TransferUiController::show` | detail | the transfer | `overview` |

   - **Slots:** each screen has exactly the slots `Screen::standard()` gives it. A detail screen has `header.actions`, `before`, `after` and `aside`. A list screen has `header.actions`, `before`, `after` and the row slot `row.actions`.
   - **`overview`:** every screen except `roster.organization` passes one built-in tab: key `overview`, label `__('roster::roster.overview')`, icon `user-circle` on `roster.user` and `squares-2x2` on the others.
   - **Layout:** each view renders `x-atrium::screen-tabs`, `x-atrium::screen-tab-panel` and the sections of every declared slot, placed as described under Design.
   - **Not declared:** the deleted-record pages, the SSO connection screen (it moves to Guardian, see Out of scope), the template download (a CSV response, not a view), and every form-post and JSON route.
   - **Proof:** a dataset feature test, `tests/Feature/Ui/ScreenDeclarationsTest.php`, with one case per row. Each case registers a `public()` tab `test.tab` and a `public()` section `test.{slot}` in every standard slot of that key. It asserts that:
     - every section's `data-testid="section-test.{slot}"` renders, and `row.actions` renders once per row, with at least one row seeded
     - no section renders for a slot the screen does not declare
     - `tab-test.tab` is in the bar, and `?tab=test.tab` renders `tab-panel-test.tab` with only the `header.actions` section around it (Atrium D-024)
     - with nothing registered, the response has no `data-testid="section-`, and no `data-testid="screen-tabs"`. The tab-bar assertion is per row: for `roster.organization`, which always passes several built-in tabs, the bar is present and holds exactly the built-in `tab-*` ids the viewer may open and no `tab-test.` or `section-` ids

4. **Access closure.** Every screen's `access()` closure is `fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allowsOn($permission, $record)`. `ScreenAccess::allowsOn(string $permission, ?Model $record): bool` is a new public static method. It asks `ScreenAccess::allows()` with the same scope the screens use for that record:

   | `$record` | Call |
   |---|---|
   | `OrganizationModel` or `TeamModel` | `allows($permission, $record)` |
   | an instance of the configured `roster.users.model` | `allows($permission, null, $record)` |
   | `RoleModel` | `allows($permission, $record->organization)` |
   | `ImpersonationModel` | `allows($permission, $record->organization)` |
   | `TransferModel` | `allows($permission, $record->organization, $record->requester)` |
   | `InvitationModel` (criterion 16) | `allows($permission, $record->organization)` |
   | null (list screens, create screens) | `allows($permission)` |
   | any other model class | `false` (deny; a new record type must be added to this table) |

   The list controllers eager-load `organization` for roles and impersonations and `organization` and `requester` for transfers, so `row.actions` adds no per-row queries (asserted by query counting on the list request). A role or impersonation with a null organization is global and takes the global check; a transfer with a null requester takes the organization-only check. Both have tests.

   **Proof:** a feature test runs one case per row, with a viewer who holds the permission in the right scope (allowed) and one who holds it elsewhere (denied). A screen test registers a `permission('roster.members.view')` section in `aside` on `roster.organization`. It renders for a viewer holding that permission in the organization, and not for a viewer holding it only in another organization. A screen test registers a `permission('roster.users.update')` section in `row.actions` on `roster.users`. It renders only in the rows the viewer may update.

5. **Order of evaluation.** A host's `authorize` closures and resolvers never run for a viewer the screen refuses, nor on the deleted pages.
   - **Refused viewer:** on every detail screen in the criterion 3 table (a dataset), a viewer the screen refuses gets 403, and counting `authorize` and resolver closures on a registered tab and a section run zero times.
   - **Deleted record:** a deleted organization or user renders its deleted page with 200 for `?tab={registered key}`, and the counting closures run zero times.

   **Proof:** feature tests: the refused-viewer dataset over every detail screen, and the deleted-record case on organizations and users (the only deleted pages).

### Organization screen

6. **Built-in tabs.** `roster.organization` passes the built-in tabs the viewer may open, in this order: `members`, `teams`, `invitations`, `roles`, `sso`, `scim`, `settings`.
   - **Permissions:** each tab keeps its current permission in the organization (the controller's private permission map, which loses its `mcp` and `activity` entries). Each keeps its current label (`roster::roster.{key}`) and icon.
   - **Default tab:** the first of those tabs. `settings` needs the screen's own permission, so the list is never empty.
   - **`?tab=` cases:**
     - a built-in key the viewer may not open answers 403, as today
     - `?tab=activity`, `?tab=mcp`, an unknown key and a non-string `?tab=` open the default tab with 200 (D-005)
     - a visible registered tab opens its panel; a hidden one answers 403
     - a registered tab whose key is a built-in passed to the screen (for example `members`) is skipped and reported by Atrium (observed with `Exceptions::fake()` and `assertReported`), and the bar shows one `tab-members`
   - **Reserved keys (D-031):** all seven built-in keys are reserved for every viewer, including the ones the viewer may not open. A registered tab named `roles` (or any other built-in key), seen by a viewer who may not open the built-in `roles` tab, is skipped and reported once per request and never shown: no `tab-roles` in the bar, and `?tab=roles` still answers 403. Roster passes the full built-in key list to Atrium as reserved keys through `Screen::reserve(...)` (see Design, "Reserved keys", and Risks: this needs an Atrium addition and is a merge blocker under criterion 21).
   - **Data:** only the open built-in tab's data is loaded, chosen by `$resolved->openTab`, and nothing is loaded while a registered tab is open. Observed by query counting by table, as criterion 9 does for `sso_identities`: the closed tabs' tables are not read.

   **Proof:** `tests/Feature/Ui/OrganizationTabBarTest.php` and `OrganizationRegisteredTabsTest.php`, re-expressed against Atrium's registry (criterion 10).
7. **Activity and MCP tabs removed.** `OrganizationUiController::offers()`, the `mcp` and `activity` branches and icons in `ui/organizations/show.blade.php`, and the controller's use of `ListRedirectDomainsAction` and `Roster::auditing()` are removed.
   - **Proof:** a feature test in `tests/Modes/Keen`, where Keen is installed and Roster audits, asserts that no `tab-activity` appears and that `?tab=activity` answers 200 on the default tab. In `tests/Authorization/ScreenControlsTest.php`, the `tab-activity` assertions and the `?tab=activity` 403 are replaced by that 200 (a forced change).

### User screen

8. **Redirect domains removed.** The redirect-domains card and the `redirectDomains` data are removed from the user overview, and so is the controller's use of `ListRedirectDomainsAction`.
   - **Removed from the code:** `src/Atrium/RedirectDomains.php`, `src/Atrium/Http/Controllers/RedirectDomainUiController.php`, `resources/views/ui/partials/redirect-domains.blade.php`, and the routes `atrium.roster.organizations.redirect-domains.store`, `atrium.roster.organizations.redirect-domains.destroy`, `atrium.roster.users.redirect-domains.store` and `atrium.roster.users.redirect-domains.destroy`.
   - **Removed tests:** `tests/Cortex/RedirectDomainsTest.php` and `tests/Feature/Ui/RedirectDomainPagesTest.php`.
   - **Removed translations:** the keys used only by removed views (exactly `mcp`, `redirect_domains`, `redirect_domains_hint` and `no_redirect_domains`; a feature test asserts those four keys are gone from `roster.php`).
   - **Proof:**
     - a feature test asserts `Route::has()` is false for each of the four route names
     - an arch test asserts `RefactorCircus\Cortex` is not used anywhere in `RefactorCircus\Roster`
     - a file-content scan test asserts no `redirect-domains` or `RedirectDomain` string in `src` or `resources`
9. **Overview tab.** `roster.user` keeps PR #5's rules through Atrium:
   - The tab bar exists only while at least one registered tab is visible. With none, or with all hidden, there is no `data-testid="tab-`, and the response HTML equals the response with nothing registered.
   - `?tab=overview`, an empty `?tab=`, an unknown key and `?tab[]=x` render the overview with 200. A hidden registered key answers 403.
   - While a registered tab is open, the overview's data is not loaded. The PR #5 test that asserts no query reads `Tables::name('sso_identities')`, with its control request, stays.
   - The redirect after a user update (`PUT` to the user update route) has a `Location` without `tab=`.

   **Proof:** `tests/Feature/Ui/UserPagesTest.php`, which keeps its two-state dataset ("no tab registered" and "one visible tab registered"), and `UserRegisteredTabsTest.php`, both registering through `Atrium::screens()->tab('roster.user', …)`.

### PR #5 behaviour, re-expressed

10. The PR #5 behaviours below hold through Atrium's registry. The existing tests `OrganizationRegisteredTabsTest`, `UserRegisteredTabsTest`, `SharedTabBarTest` and `OrganizationTabBarTest` in `tests/Feature/Ui` are rewritten to register through `Atrium::screens()->tab('roster.organization' | 'roster.user', TabDefinition::make(...))`, using `RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition`. They keep these cases:
    - registered tabs follow the visible built-ins in registration order, and a hidden middle tab is absent (`assertSeeInOrder`)
    - a label containing markup is escaped, and an unknown icon renders like `squares-2x2`
    - a counting resolver runs once for `?tab={key}`, and zero times for a built-in tab, no `?tab=`, a hidden key, an unknown key and a non-string `?tab=`
    - on the view path, a resolver returning `screen`, `record` and `tab` does not override the host's values. On the component path the data arrives as one `data` prop, so a resolver returning `slot`, `attributes` or a dashed key still gives 200.
    - visibility on both screens: permission only, closure only, `public()`, neither (hidden, `?tab=` 403), both passing, one failing. A permission nothing grants is hidden.
    - a throwing `authorize` is hidden, reported once and called once, and the page answers 200, re-checked across two requests with `app()->forgetScopedInstances()`
    - a throwing resolver on the open tab propagates (500)
    - all-digit keys `123` and `007` appear in the bar and open through `?tab=`
    - only the open tab has `aria-current="page"`, on both screens

    Where PR #5 and Atrium differ, Atrium's behaviour is what the tests assert (listed under Design, "Changed from PR #5").

### Other packages' UI removed

11. **Audit trail removed.** No `<x-atrium::audit-trail>` remains. It is removed from `ui/roles/show`, `ui/teams/show`, `ui/users/partials/overview` and the organization activity branch. Screens declare no audited subject. `RosterPlugin::navigation()` no longer calls `historyNavItem()`.
    - **Removed tests:** `tests/Feature/HistoryNavigationTest.php`, `tests/Feature/Ui/AuditTrailTest.php`, and the "shows a record's history only to those who may read the audit log" test in `tests/Authorization/ScreenControlsTest.php`.
    - **Proof:**
      - a test asserts that no file under `resources/views` contains `x-atrium::audit`
      - a test asserts that `RosterPlugin::navigation()` returns no item whose route starts with `atrium.history`
      - a file-content scan test asserts `historyNavItem` and `audit-trail` appear nowhere in `src` or `resources`

### Atrium is suggested

12. **`composer.json`.** `refactor-circus/atrium` moves from `require` to `require-dev` (`dev-main`) and `suggest`. `require` keeps `php`, `refactor-circus/keystone`, `laravel/framework` and `laravel/mcp`.
    - **Suggest wording:**
      - `refactor-circus/atrium`: "The Atrium dashboard: Roster's admin screens, widgets and search (dev-main)."
      - `refactor-circus/cortex`: "Lets Cortex agents use the Roster tools (dev-main)."
      - `refactor-circus/keen`: "Records Roster's changes in the suite-wide audit log (dev-main)."
    - `extra.atrium.plugins` stays.
    - **Conflict (D-025, D-030):** `composer.json` gains `"conflict": {"refactor-circus/atrium": "<0.1.0"}`. v0.1.0 is Atrium's first tagged release and the first to ship the screen-extensions registry (Atrium PR #16; its Unreleased CHANGELOG lists "Screen extensions"), so any older tagged Atrium is refused. `dev-main` installs are not version-compared by Composer; they are covered by the merge gate (criterion 21).
    - **Proof:** a test reads `composer.json` and asserts that the only `refactor-circus/*` package in `require` is `refactor-circus/keystone`, that `refactor-circus/atrium` is in `require-dev`, that `suggest` holds the three strings above exactly, and that `conflict.refactor-circus/atrium` is `<0.1.0`.
13. **Arch tests.** These tests in `tests/ArchTest.php` pass:
    - `expect('RefactorCircus\Atrium')->toOnlyBeUsedIn('RefactorCircus\Roster\Atrium')`
    - outside `RefactorCircus\Roster\Atrium`, only `RefactorCircus\Roster\Atrium\AtriumServiceProvider` is used, and only by `RosterServiceProvider` (criterion 16)
    - `RefactorCircus\Roster\Atrium\ScreenAccess` uses nothing in `RefactorCircus\Atrium`

    To meet them, the provider's user-status cache listener uses a new `RefactorCircus\Roster\Support\Users::STATUS_COUNTS_CACHE` instead of `RosterPlugin::STATUS_COUNTS_CACHE`. That constant stays, set to `Users::STATUS_COUNTS_CACHE`. The `@rosterCan` registration and its `ScreenAccess` import move from `RosterServiceProvider` to `AtriumServiceProvider`; `@rosterCan` is used only by Atrium views.
14. **Atrium-free test mode (D-025, the in-suite half).** `tests/AtriumUnavailableTestCase.php` extends `TestCase` and overrides `getPackageProviders()` to register `McpServiceProvider` and `RosterServiceProvider` only, and makes `RosterServiceProvider` skip `AtriumServiceProvider` as if `class_exists('RefactorCircus\Atrium\Atrium')` were false: it sets `RosterServiceProvider::$withoutAtrium = true` (a new `@internal` `public static bool`, default false) before the application boots, and resets it to false in `tearDown()`. `tests/Pest.php` maps it to `tests/Modes/AtriumUnavailable`, following the existing `SsoUnavailable` and `TransfersUnavailable` modes. `AtriumUnavailableTest.php` proves:
    - the application boots
    - `Route::has('atrium.roster.users.index')` is false
    - `POST` to `roster.organizations.store` answers 201, and `GET` on `roster.users.index` answers 200
    - `mcpTool(ListUsersTool::class)` answers without error
    - the invitation page answers 200 with the plain view (no `x-atrium` markup, `data-testid="invitation-plain"`), and `roster::impersonation-banner` renders the plain banner during an impersonation (criterion 16)

    It runs in `composer test`. Atrium stays in `require-dev`, so its classes remain autoloadable here: this mode proves only that the Atrium wiring is not registered, not that Roster is free of Atrium references. The local guard for that is the arch tests plus a file-content scan test that no file in `src` outside `src/Atrium` contains the string `RefactorCircus\\Atrium`, other than the one `class_exists('RefactorCircus\Atrium\Atrium')` check in `RosterServiceProvider`. The CI job in criterion 15 is the real proof.
15. **CI lane without Atrium (D-025, the CI half).** `.github/workflows/tests.yml` gains a `without-atrium` job. It runs `composer remove --dev --no-update refactor-circus/atrium refactor-circus/cortex refactor-circus/impex` (Cortex and Impex require Atrium), plus every other dev package that requires Atrium (Keen is checked and added if it does), then `composer update`, then a step that fails unless `php -r "exit(class_exists('RefactorCircus\\\\Atrium\\\\Atrium') ? 1 : 0);"` succeeds, then `vendor/bin/pest tests/Modes/AtriumUnavailable`. With Atrium's classes absent, any reference to them outside `src/Atrium` fails here.
    - **Impex:** removing Impex is a side effect of Atrium being required by it, not a second requirement of this spec. Impex-less boot is guarded only by this lane and the existing `TransfersUnavailable` mode; no further criterion covers it (see Out of scope).
    - **Proof:** the job is green on the PR. The README "Testing" section gives the commands to reproduce it in a scratch worktree.
16. **Roster's Atrium seam: invitation page and impersonation banner (D-027, D-029, D-032).**
    - **Seam.** A new `RefactorCircus\Roster\Atrium\AtriumServiceProvider` (extends `Illuminate\Support\ServiceProvider`). `RosterServiceProvider::register()` registers it only when `class_exists('RefactorCircus\Atrium\Atrium')` (a string, never `::class`) and `RosterServiceProvider::$withoutAtrium` is false. Its `register()` binds the invitation page; its `boot()` registers `@rosterCan` (`Blade::if('rosterCan', ScreenAccess::allows(...))`) and shares `rosterUsesAtrium = true` with views (`View::share`).
    - **Invitation page.** `InvitationWebController::show()` returns `app(RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage::class)->render($invitation, $token, $request)`. The interface has one method, `render(InvitationModel $invitation, string $token, Request $request): View`. The default binding, registered in `InvitationServiceProvider::register()`, is `Domains\Invitation\Services\PlainInvitationPage`, which renders `roster::invitations.show`: rewritten as plain HTML with no `x-atrium` components and no `<style>` block (inline `style=` attributes are allowed here only; the view's root has `data-testid="invitation-plain"`), keeping the accept and decline forms, the organization name and the team list. `AtriumServiceProvider` rebinds the interface to `RefactorCircus\Roster\Atrium\InvitationPage`, which builds `Screen::standard('roster.invitation', 'detail')->record($invitation)->access(...)` (criterion 4's closure; guests are denied), resolves it and renders `roster::ui.invitations.show`, the current Atrium markup moved there, with the screen's tab bar, panel and slots as criterion 3 describes. Both receive the same view data: `invitation`, `organization`, `teams` (names, sorted) and `token`.
    - **Banner.** `roster::impersonation-banner` stays the name host layouts include. It becomes a dispatcher: `@include(($rosterUsesAtrium ?? false) ? 'roster::ui.impersonation-banner' : 'roster::partials.impersonation-banner-plain')`, passing `bannerClass` through. `ui/impersonation-banner.blade.php` holds the current `x-atrium::banner standalone` markup; `partials/impersonation-banner-plain.blade.php` is plain HTML with inline styles (it shows on the app's own pages), the same text, `data-testid="impersonation-banner"` and the `leave-impersonation` button.
    - **Standards exception.** The two plain views are the only Roster views not built from Atrium components; `StylesTest` excludes exactly those two files, and a `docs/agent/standards-feedback.md` row records the departure from `roster/atrium-views`.

    **Proof:** criterion 14's invitation and banner cases (plain); a feature test with Atrium present asserting the invitation page renders the Atrium markup, `roster.invitation` is declared (a `public()` section in `aside` renders), the banner renders the Atrium markup, and `@rosterCan` is registered; a unit test that the default `InvitationPage` binding is `PlainInvitationPage` when `$withoutAtrium` is true.
17. **Pest bootstrap without Atrium.** `tests/Pest.php` and `tests/TestCase.php` load without Atrium's classes: imports of Atrium classes stay `::class`-only, and the trait in criterion 2 is guarded. **Proof:** the criterion 15 job.

### Documentation

18. **README and Boost skill.** The README "Registered tabs" section and the Boost skill's matching section are replaced by "Screen extensions". It contains:
    - the table of screen keys, kinds, records and slots from criterion 3
    - the organization's built-in tab keys
    - the access mapping from criterion 4
    - a registration example with `Atrium::screens()->tab('roster.organization', …)` and `->section('roster.user', 'aside', …)`
    - a pointer to Atrium's README

    The package `CLAUDE.md` drops its `audit-trail` and `RedirectDomains` lines, says screens declare themselves with `Screen::standard()`, and says Atrium is optional. The "MCP redirect domains" README section is removed. Installation says Atrium is optional (`composer require refactor-circus/atrium` for the screens).
    - **Proof:** `tests/Feature/DocsTest.php` checks the "Screen extensions" section in both files for every screen key from criterion 3, the organization's built-in tab keys, `allowsOn` and the access mapping wording, `Atrium::screens()` and `aside`, and no longer checks `Registered tabs`.
19. **CHANGELOG.** `CHANGELOG.md`'s Unreleased "Breaking" section has an upgrade note. It opens with "Roster no longer requires `refactor-circus/atrium`; require it to keep the dashboard", and lists the `atrium.roster.*` route names as unavailable without it (`route()` calls to them throw). It then covers:
    - the removed `Tabs`, `TabDefinition`, `TabVisibility` and the two partials, and how to re-register on `roster.organization` / `roster.user` with Atrium's registry
    - the changed view variables and closure signatures
    - the removed `activity` and `mcp` tabs: old `?tab=activity` and `?tab=mcp` links open the default tab, and the history and MCP domains come from Keen and Cortex
    - the removed redirect-domain routes, class, controller and card
    - the removed audit trails and History nav item
    - a re-publish note for apps that published `roster-views` (the invitation page and banner views moved under `ui/`, and plain versions replaced them)
    - the `conflict` on `refactor-circus/atrium` `<0.1.0`
    - registered tabs named like any organization built-in are now skipped for every viewer (D-031)

    **Proof:** a DocsTest case asserts the Unreleased section mentions `Tabs`, `?tab=activity`, `?tab=mcp`, `refactor-circus/atrium` and `conflict`.

### Gate

20. `tests/ArchTest.php` passes, as do `composer test` and `composer test:browser`. `composer test` excludes the Browser suite, so `composer test:browser` is run in addition. Existing tests change only where this spec's decisions force it: removed classes, partials, routes and components; Atrium's tab bar markup; `?tab=activity`/`mcp` now 200. The forced changes are listed under Design. The PR lists each changed test by file and test name.

21. **Merge gate.** The PR deletes the activity and MCP tabs, the redirect-domains UI and the audit trails in one step, so it must not merge ahead of their replacements. `meta.yml` and RingMaster record four hard blockers: Atrium PR #16, the Atrium change that adds `Screen::reserve()` (criterion 6, D-031), Cortex `register-roster-redirect-domains` and Keen `history-section-by-capability`. The PR description confirms each is merged or in the same release train. Roster stores no redirect-domain data (Cortex keeps it), so the removal loses no data; the removed routes and `RedirectDomains` class are public surface and the CHANGELOG says so.
    - **Proof:** the PR description lists the four blockers with their status, and `meta.yml` names them.

## Design

### Screens

- **Controllers.** Controllers in `src/Atrium/Http/Controllers` build their `Screen` through a private helper on `Concerns\AuthorizesScreens`: `screen(string $key, string $kind, ?Model $record = null): Screen`. It applies `Screen::standard()`, `record()` and the `access()` closure from criterion 4. `ScreenAccess::allowsOn()` sits beside `allows()`. No new class, binding or config.
- **Organization tabs.** `OrganizationUiController` keeps a private ordered permission map without `mcp`/`activity`. It filters by `ScreenAccess::allows($permission, $model)` and passes the result to `tabs()` with labels `__('roster::roster.'.$key)` and the existing icon map. It calls `defaultTab()` with the first entry, and `reserve(...array_keys($map))` with all seven keys.
- **Reserved keys (D-031).** Atrium reserves only the keys passed to `Screen::tabs()`, and its tab bar shows every one of them, so Roster cannot reserve a built-in it hides with the current Atrium API. Roster relies on an Atrium addition: `Screen::reserve(string ...$keys): self`, whose keys are reserved like built-in keys (a registered tab using one is skipped and reported per request) but are not tabs and never render. Until that lands, criterion 6's reserved-key case cannot pass; it is listed as a merge blocker (criterion 21).
- **Integration config (D-026).** Roster adds no integration config (no screen maps or slot entries in `config/roster.php`); it only declares screens. D-026's rule therefore has no surface in this spec. Malformed registrations by other packages are handled by Atrium. It checks a requested built-in the viewer may not open (403) before `resolve()`, then loads data by `match ($resolved->openTab)`.
- **User screen.** `UserUiController::show` calls `tabs([['key' => 'overview', 'label' => __('roster::roster.overview'), 'icon' => 'user-circle']])` and loads `overview()` only while `$resolved->openRegisteredTab === null`.
- **Layout, following Atrium's own `settings/show` view:**
  - `header.actions` renders inside `x-atrium::page-header`'s actions slot, after Roster's own actions. The slot is opened only when Roster has actions or the slot has sections.
  - `x-atrium::screen-tabs` renders below the header.
  - While a registered tab is open, `x-atrium::screen-tab-panel` replaces the page content. Only the impersonation banner and the flash stay above it.
  - Otherwise `before` renders, then the content, then `after`.
  - On detail screens, the content and `aside` sit in a `lg:grid-cols-3` grid (content `lg:col-span-2`, `aside` in `<aside data-testid="screen-aside">`) only while `aside` has visible sections. With none, the markup is unchanged.
  - `row.actions` renders inside each row's last cell, after Roster's own controls.
- **Styles.** Only Atrium components and safelisted utilities are used (`StylesTest`).

### Changed from PR #5 (Atrium's behaviour wins; CHANGELOG notes it)

- **Reserved keys:** a registered tab with a built-in key (any of the seven, for every viewer) is skipped and reported per request, not thrown at registration. `activity` and `mcp` are no longer reserved.
- **Duplicate keys:** throw in `local`/`testing`, and are reported and skipped elsewhere.
- **Closures:** `authorize(Request, ?Model, Screen)` and `resolve(?Model, Screen)`. Old two- and one-argument closures still work.
- **View variables:** a tab view receives `screen`, `record` and `tab` over the resolver data, no longer `organization`/`user`, `tabs` or `registeredTab`. A component receives only `data`.
- **Tab bar:** the bar is `x-atrium::screen-tabs`. It does not wrap, has no `aria-label`, renders nothing with fewer than two visible tabs, and its links keep the query string except `page`. Built-in `data-testid="tab-{key}"` ids are unchanged.

### Forced changes to existing tests

- **Deleted:**
  - `tests/Unit/TabDefinitionTest.php`, `TabsTest.php` and `OrganizationTabsTest.php`
  - `tests/Cortex/RedirectDomainsTest.php`
  - `tests/Feature/Ui/RedirectDomainPagesTest.php` and `AuditTrailTest.php`
  - `tests/Feature/HistoryNavigationTest.php`
  - the history test in `ScreenControlsTest`
- **Rewritten registration:** `OrganizationRegisteredTabsTest`, `UserRegisteredTabsTest`, `SharedTabBarTest`, `OrganizationTabBarTest` (href and single-tab expectations) and `UserPagesTest` (registration call).
- **Changed:** `ScreenControlsTest` ("lists only the organization tabs…": the `activity` 403 becomes 200), `DocsTest` (registered-tabs case), and any invitation-page and impersonation-banner tests whose markup assertions move with the views (criterion 16).
- **Browser:** browser tests that click `@tab-*` keep passing, because the ids are unchanged.

## Out of scope

- The SSO connection screen and the `sso`/`scim` built-in tabs, which leave in `remove-sso-and-scim` and the Guardian spec. The SSO screen keeps its current markup and is not declared.
- Registering the history section (Keen), the `keen.activity` tab, the `cortex.mcp` tab and the redirect-domains aside (Cortex). Those specs do it from their own config defaults (Atrium D-019).
- The Roster seams PR (`auth-extension-seams`), and any change to the HTTP API or MCP surface.
- Declaring the deleted-record pages, the template download (a CSV response) or Roster's other web pages, apart from the invitation page (criterion 16).
- The Atrium `Screen::reserve()` addition itself: it is Atrium work and needs its own Atrium spec (criterion 6, Risks).
- Impex-less boot as its own requirement. The without-Atrium lane happens to remove Impex too; that is guarded only by the lane and the existing `TransfersUnavailable` mode.

## Risks

- **Reserved keys need Atrium (D-031):** Atrium's `Screen` has no way to reserve a key without rendering it as a tab, so criterion 6's reserved-key case depends on an Atrium `Screen::reserve()` addition, which needs its own Atrium spec. It is a hard merge blocker (criterion 21). The group that wires it runs last so the rest of the spec can build first.
- **Merge order (enforced by criterion 21):** this PR removes the MCP domains and activity UI. Cortex `register-roster-redirect-domains` and Keen `history-section-by-capability` must merge with or before it, and Atrium PR #16 before all of them.
- **Lane coverage:** the without-Atrium lane runs only `tests/Modes/AtriumUnavailable`. It also removes Impex and Cortex, so Roster must boot without Impex as well (the `TransfersUnavailable` mode already covers the Transfer domain's own checks). It is not run by local `composer test`. The arch tests and the scan test are the local guard.
- **Conflict reach:** Composer compares the `<0.1.0` conflict only against tagged Atrium versions. A `dev-main` Atrium from before PR #16 is not refused; the merge gate covers it.
- **Published views:** apps that published `roster-views` keep the old tabs partial references and the old invitation and banner views, and fail or keep the old look until they re-publish.

## Changelog

- Applied D-025: in-suite AtriumUnavailable mode and the CI lane named as the two halves (criteria 14, 15); `conflict` on old Atrium (criterion 12).
- Applied D-026: noted that Roster adds no integration config, so the rule has no surface here (Design).
- Applied D-027, D-029, D-032: Option B, `Roster\Atrium\AtriumServiceProvider` registered only when Atrium exists; invitation page and banner have plain and Atrium views; `@rosterCan` moves there (criteria 13, 14, 16, 19). Q1 removed.
- Applied D-028: one spec and PR (Goal).
- Applied D-030: `conflict` `refactor-circus/atrium: <0.1.0` (criteria 12, 19).
- Applied D-031: every built-in key reserved for every viewer (criterion 6, Design); needs an Atrium `Screen::reserve()` addition, added as a merge blocker (criterion 21).
