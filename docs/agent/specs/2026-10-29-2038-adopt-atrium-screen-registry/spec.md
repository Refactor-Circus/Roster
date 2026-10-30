# Adopt Atrium screen registry

## Goal

Every Roster Atrium screen declares itself to Atrium's screen extensions registry (Atrium spec `2026-10-29-2037-screen-extensions-registry`, PR #16), so any package can add tabs and sections to it, and Roster stops rendering other packages' UI. Roster's own tab registry from PR #5 is deleted and its behaviour carries over through Atrium's. Atrium becomes a suggested package: Roster boots, and serves its HTTP API and MCP server, without it.

## Acceptance criteria

### Roster's tab registry is gone

1. **Deleted.** These are removed: `src/Atrium/Tabs.php`, `src/Atrium/TabDefinition.php`, `src/Atrium/TabVisibility.php`, `resources/views/ui/partials/tabs.blade.php` and `resources/views/ui/partials/registered-tab.blade.php`, plus the `Tabs` singleton and `TabVisibility` scoped bindings in `RosterServiceProvider::register()`. The registry's own unit tests go with them: `tests/Unit/TabDefinitionTest.php`, `tests/Unit/TabsTest.php` and `tests/Unit/OrganizationTabsTest.php`. Atrium's tests own that behaviour now.
   - **Proof:** a test in `tests/ArchTest.php` asserts that `class_exists()` is false for the three classes and `view()->exists()` is false for both partials.
   - **Done-step:** a grep for `Atrium\Tabs`, `TabDefinition`, `TabVisibility`, `partials.tabs` and `registered-tab` over `src`, `resources`, `tests`, `README.md` and the Boost skill finds nothing outside CHANGELOG history. The PR lists that grep.
2. **Test reset.** `tests/Pest.php` stops flushing `Tabs`. Instead it applies Atrium's `RefactorCircus\Atrium\Testing\ResetsScreenExtensions` to `Feature` and `Authorization`. That `uses()` call only runs when `trait_exists(ResetsScreenExtensions::class)`, so `Pest.php` still loads without Atrium (criterion 17).
   - **Proof:** a test registers a tab on `roster.organization` inside its body. A second test, in the same file, asserts the tab is gone.

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
     - with nothing registered, the response has no `data-testid="screen-tabs"` and no `data-testid="section-`

4. **Access closure.** Every screen's `access()` closure is `fn (string $permission, Request $request, ?Model $record): bool => ScreenAccess::allowsOn($permission, $record)`. `ScreenAccess::allowsOn(string $permission, ?Model $record): bool` is a new public static method. It asks `ScreenAccess::allows()` with the same scope the screens use for that record:

   | `$record` | Call |
   |---|---|
   | `OrganizationModel` or `TeamModel` | `allows($permission, $record)` |
   | an instance of the configured `roster.users.model` | `allows($permission, null, $record)` |
   | `RoleModel` | `allows($permission, $record->organization)` |
   | `ImpersonationModel` | `allows($permission, $record->organization)` |
   | `TransferModel` | `allows($permission, $record->organization, $record->requester)` |
   | anything else, or null | `allows($permission)` |

   **Proof:** a feature test runs one case per row, with a viewer who holds the permission in the right scope (allowed) and one who holds it elsewhere (denied). A screen test registers a `permission('roster.members.view')` section in `aside` on `roster.organization`. It renders for a viewer holding that permission in the organization, and not for a viewer holding it only in another organization. A screen test registers a `permission('roster.users.update')` section in `row.actions` on `roster.users`. It renders only in the rows the viewer may update.

5. **Order of evaluation.** A host's `authorize` closures and resolvers never run for a viewer the screen refuses, nor on the deleted pages.
   - **Refused viewer:** on `roster.organization`, `roster.user` and `roster.team`, a viewer the screen refuses gets 403, and counting `authorize` and resolver closures on a registered tab and a section run zero times.
   - **Deleted record:** a deleted organization or user renders its deleted page with 200 for `?tab={registered key}`, and the counting closures run zero times.

   **Proof:** feature tests on those three screens.

### Organization screen

6. **Built-in tabs.** `roster.organization` passes the built-in tabs the viewer may open, in this order: `members`, `teams`, `invitations`, `roles`, `sso`, `scim`, `settings`.
   - **Permissions:** each tab keeps its current permission in the organization (the controller's private permission map, which loses its `mcp` and `activity` entries). Each keeps its current label (`roster::roster.{key}`) and icon.
   - **Default tab:** the first of those tabs. `settings` needs the screen's own permission, so the list is never empty.
   - **`?tab=` cases:**
     - a built-in key the viewer may not open answers 403, as today
     - `?tab=activity`, `?tab=mcp`, an unknown key and a non-string `?tab=` open the default tab with 200 (D-005)
     - a visible registered tab opens its panel; a hidden one answers 403
     - a registered tab whose key is a built-in passed to the screen (for example `members`) is skipped and reported by Atrium, and the bar shows one `tab-members`
   - **Data:** only the open built-in tab's data is loaded, chosen by `$resolved->openTab`, and nothing is loaded while a registered tab is open.

   **Proof:** `tests/Feature/Ui/OrganizationTabBarTest.php` and `OrganizationRegisteredTabsTest.php`, re-expressed against Atrium's registry (criterion 10).
7. **Activity and MCP tabs removed.** `OrganizationUiController::offers()`, the `mcp` and `activity` branches and icons in `ui/organizations/show.blade.php`, and the controller's use of `ListRedirectDomainsAction` and `Roster::auditing()` are removed.
   - **Proof:** a feature test in `tests/Modes/Keen`, where Keen is installed and Roster audits, asserts that no `tab-activity` appears and that `?tab=activity` answers 200 on the default tab. In `tests/Authorization/ScreenControlsTest.php`, the `tab-activity` assertions and the `?tab=activity` 403 are replaced by that 200 (a forced change).

### User screen

8. **Redirect domains removed.** The redirect-domains card and the `redirectDomains` data are removed from the user overview, and so is the controller's use of `ListRedirectDomainsAction`.
   - **Removed from the code:** `src/Atrium/RedirectDomains.php`, `src/Atrium/Http/Controllers/RedirectDomainUiController.php`, `resources/views/ui/partials/redirect-domains.blade.php`, and the routes `atrium.roster.organizations.redirect-domains.store`, `atrium.roster.organizations.redirect-domains.destroy`, `atrium.roster.users.redirect-domains.store` and `atrium.roster.users.redirect-domains.destroy`.
   - **Removed tests:** `tests/Cortex/RedirectDomainsTest.php` and `tests/Feature/Ui/RedirectDomainPagesTest.php`.
   - **Removed translations:** the keys used only by removed views (`mcp`, `redirect_domains`, `redirect_domains_hint`, `no_redirect_domains`, and any other key no remaining file uses).
   - **Proof:**
     - a feature test asserts `Route::has()` is false for each of the four route names
     - an arch test asserts `RefactorCircus\Cortex` is not used anywhere in `RefactorCircus\Roster`
     - a done-step grep shows no `redirect-domains` or `RedirectDomain` in `src`, `resources` or `tests`
9. **Overview tab.** `roster.user` keeps PR #5's rules through Atrium:
   - The tab bar exists only while at least one registered tab is visible. With none, or with all hidden, there is no `data-testid="tab-` and the page is unchanged.
   - `?tab=overview`, an empty `?tab=`, an unknown key and `?tab[]=x` render the overview with 200. A hidden registered key answers 403.
   - While a registered tab is open, the overview's data is not loaded. The PR #5 test that asserts no query reads `Tables::name('sso_identities')`, with its control request, stays.
   - Redirects back to the page carry no `tab=`.

   **Proof:** `tests/Feature/Ui/UserPagesTest.php`, which keeps its two-state dataset ("no tab registered" and "one visible tab registered"), and `UserRegisteredTabsTest.php`, both registering through `Atrium::screens()->tab('roster.user', …)`.

### PR #5 behaviour, re-expressed

10. Roster PR #5's criteria 6 to 16 hold through Atrium's registry. The existing tests `OrganizationRegisteredTabsTest`, `UserRegisteredTabsTest`, `SharedTabBarTest` and `OrganizationTabBarTest` in `tests/Feature/Ui` are rewritten to register through `Atrium::screens()->tab('roster.organization' | 'roster.user', TabDefinition::make(...))`, using `RefactorCircus\Atrium\Domains\Screens\Data\TabDefinition`. They keep these cases:
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
      - the PR lists a done-step grep for `historyNavItem` and `audit-trail` over `src`, `resources` and `tests`

### Atrium is suggested

12. **`composer.json`.** `refactor-circus/atrium` moves from `require` to `require-dev` (`dev-main`) and `suggest`. `require` keeps `php`, `refactor-circus/keystone`, `laravel/framework` and `laravel/mcp`.
    - **Suggest wording:**
      - `refactor-circus/atrium`: "The Atrium dashboard: Roster's admin screens, widgets and search (dev-main)."
      - `refactor-circus/cortex`: "Lets Cortex agents use the Roster tools (dev-main)."
      - `refactor-circus/keen`: "Records Roster's changes in the suite-wide audit log (dev-main)."
    - `extra.atrium.plugins` stays.
    - **Proof:** a test reads `composer.json` and asserts that the only `refactor-circus/*` package in `require` is `refactor-circus/keystone`, and that `refactor-circus/atrium` is in `suggest`.
13. **Arch tests.** These tests in `tests/ArchTest.php` pass:
    - `expect('RefactorCircus\Atrium')->toOnlyBeUsedIn('RefactorCircus\Roster\Atrium')`
    - outside `RefactorCircus\Roster\Atrium`, only `RefactorCircus\Roster\Atrium\ScreenAccess` is used, and only by `RosterServiceProvider` for `@rosterCan`
    - `RefactorCircus\Roster\Atrium\ScreenAccess` uses nothing in `RefactorCircus\Atrium`

    To meet them, the provider's user-status cache listener uses a new `RefactorCircus\Roster\Support\Users::STATUS_COUNTS_CACHE` instead of `RosterPlugin::STATUS_COUNTS_CACHE`. That constant stays, set to `Users::STATUS_COUNTS_CACHE`.
14. **Atrium-free test mode.** `tests/AtriumUnavailableTestCase.php` extends `TestCase` and overrides `getPackageProviders()` to register `McpServiceProvider` and `RosterServiceProvider` only. `tests/Pest.php` maps it to `tests/Modes/AtriumUnavailable`, following the existing `SsoUnavailable` and `TransfersUnavailable` modes. `AtriumUnavailableTest.php` proves:
    - the application boots
    - `Route::has('atrium.roster.users.index')` is false
    - `POST` to `roster.organizations.store` answers 201, and `GET` on `roster.users.index` answers 200
    - `mcpTool(ListUsersTool::class)` answers without error
    - the invitation page answers 200 (criterion 16)

    It runs in `composer test`.
15. **CI lane without Atrium.** `.github/workflows/tests.yml` gains a `without-atrium` job. It runs `composer remove --dev --no-update refactor-circus/atrium refactor-circus/cortex refactor-circus/impex` (Cortex and Impex require Atrium), then `composer update`, then `vendor/bin/pest tests/Modes/AtriumUnavailable`. With Atrium's classes absent, any reference to them outside `src/Atrium` fails here.
    - **Proof:** the job is green on the PR. The README "Testing" section gives the commands to reproduce it in a scratch worktree.
16. **Invitation page and impersonation banner without Atrium (pending Q1).** Under Q1's recommended answer:
    - With Atrium installed, the invitation page renders through Atrium as the screen `roster.invitation` (detail; the record is the invitation; the synthetic `overview` tab).
    - Without Atrium, it renders a plain Roster view, and `roster::impersonation-banner` renders plain markup.

    **Proof:** criterion 14's invitation-page case, and a feature test asserting `roster.invitation` is declared when Atrium is present.
17. **Pest bootstrap without Atrium.** `tests/Pest.php` and `tests/TestCase.php` load without Atrium's classes: imports of Atrium classes stay `::class`-only, and the trait in criterion 2 is guarded. **Proof:** the criterion 15 job.

### Documentation

18. **README and Boost skill.** The README "Registered tabs" section and the Boost skill's matching section are replaced by "Screen extensions". It contains:
    - the table of screen keys, kinds, records and slots from criterion 3
    - the organization's built-in tab keys
    - the access mapping from criterion 4
    - a registration example with `Atrium::screens()->tab('roster.organization', …)` and `->section('roster.user', 'aside', …)`
    - a pointer to Atrium's README

    The package `CLAUDE.md` drops its `audit-trail` and `RedirectDomains` lines, says screens declare themselves with `Screen::standard()`, and says Atrium is optional. The "MCP redirect domains" README section is removed. Installation says Atrium is optional (`composer require refactor-circus/atrium` for the screens).
    - **Proof:** `tests/Feature/DocsTest.php` checks the "Screen extensions" section in both files for `roster.organization`, `roster.user`, `Atrium::screens()` and `aside`, and no longer checks `Registered tabs`.
19. **CHANGELOG.** `CHANGELOG.md`'s Unreleased "Breaking" section has an upgrade note covering:
    - the removed `Tabs`, `TabDefinition`, `TabVisibility` and the two partials, and how to re-register on `roster.organization` / `roster.user` with Atrium's registry
    - the changed view variables and closure signatures
    - the removed `activity` and `mcp` tabs: old `?tab=activity` and `?tab=mcp` links open the default tab, and the history and MCP domains come from Keen and Cortex
    - the removed redirect-domain routes, class, controller and card
    - the removed audit trails and History nav item
    - Atrium moving to `suggest` (apps using the screens must require it)
    - a re-publish note for apps that published `roster-views`

    **Proof:** a DocsTest case asserts the Unreleased section mentions `Tabs`, `?tab=activity`, `?tab=mcp` and `refactor-circus/atrium`.

### Gate

20. `tests/ArchTest.php` passes, as do `composer test` and `composer test:browser`. `composer test` excludes the Browser suite, so `composer test:browser` is run in addition. Existing tests change only where this spec's decisions force it: removed classes, partials, routes and components; Atrium's tab bar markup; `?tab=activity`/`mcp` now 200. The forced changes are listed under Design. The PR lists each changed test by file and test name.

## Design

### Screens

- **Controllers.** Controllers in `src/Atrium/Http/Controllers` build their `Screen` through a private helper on `Concerns\AuthorizesScreens`: `screen(string $key, string $kind, ?Model $record = null): Screen`. It applies `Screen::standard()`, `record()` and the `access()` closure from criterion 4. `ScreenAccess::allowsOn()` sits beside `allows()`. No new class, binding or config.
- **Organization tabs.** `OrganizationUiController` keeps a private ordered permission map without `mcp`/`activity`. It filters by `ScreenAccess::allows($permission, $model)` and passes the result to `tabs()` with labels `__('roster::roster.'.$key)` and the existing icon map. It calls `defaultTab()` with the first entry. It checks a requested built-in the viewer may not open (403) before `resolve()`, then loads data by `match ($resolved->openTab)`.
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

- **Reserved keys:** a registered tab with a built-in key is skipped and reported per request, not thrown at registration. `activity` and `mcp` are no longer reserved.
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
- **Changed:** `ScreenControlsTest` ("lists only the organization tabs…": the `activity` 403 becomes 200) and `DocsTest` (registered-tabs case).
- **Browser:** browser tests that click `@tab-*` keep passing, because the ids are unchanged.

## Out of scope

- The SSO connection screen and the `sso`/`scim` built-in tabs, which leave in `remove-sso-and-scim` and the Guardian spec. The SSO screen keeps its current markup and is not declared.
- Registering the history section (Keen), the `keen.activity` tab, the `cortex.mcp` tab and the redirect-domains aside (Cortex). Those specs do it from their own config defaults (Atrium D-019).
- The Roster seams PR (`auth-extension-seams`), and any change to the HTTP API or MCP surface.
- Declaring the deleted-record pages, the template download or Roster's other web pages, apart from the invitation page (Q1).

## Risks and open questions

**Q1. How do the invitation page and the impersonation banner work without Atrium?** Both live outside `src/Atrium` (`InvitationWebController` renders `roster::invitations.show`; the banner is included in host layouts), and both use `x-atrium::guest`, `button`, `alert` and `banner`. Without Atrium the invitation page, which every invitation email links to, fails to render. The addendum also lists the invitation page as a declared screen, which needs Atrium classes outside `src/Atrium`.
- **Option A:** plain views always. Roster's own minimal HTML with no Atrium components. The page is never declared as a screen, and installs with Atrium lose the Atrium look.
- **Option B (recommended):** a Roster-owned seam. `RosterServiceProvider` registers `RefactorCircus\Roster\Atrium\AtriumServiceProvider` only when `class_exists('RefactorCircus\Atrium\Atrium')`. That is a string check, as Keystone's `registerAtriumPlugin()` does, and is allowed by architecture/dependencies. That provider points the invitation page and banner at Atrium-component views, declares `roster.invitation` and registers `@rosterCan`. Without it, plain views render. This also gives one place for any future Atrium-only wiring.
- **Option C:** treat both as Atrium-only. Without Atrium, the invitation page routes are not registered. This breaks invitation emails, so it is not recommended.

**Risks:**
- **Reserved keys:** Roster passes only the built-in tabs the viewer may open. A registered tab named like a built-in the viewer cannot open (for example `sso`) is not reserved for that viewer, and shows. Opening it answers 403, because Roster checks built-in keys first. `vendor.key` keys (Atrium D-008) make this unlikely.
- **Merge order:** this PR removes the MCP domains and activity UI. Cortex `register-roster-redirect-domains` and Keen `history-section-by-capability` must merge with or before it, and Atrium PR #16 before all of them.
- **Lane coverage:** the without-Atrium lane runs only `tests/Modes/AtriumUnavailable`. It also removes Impex and Cortex, so Roster must boot without Impex as well (the `TransfersUnavailable` mode already covers the Transfer domain's own checks). It is not run by local `composer test`. The arch tests are the local guard.
- **Published views:** apps that published `roster-views` keep the old tabs partial references, and fail until they re-publish.
