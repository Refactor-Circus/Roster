# Tasks: Registered tabs on organization and user screens

## Group 1: Tab registry, definition and per-request memo
depends on: none
files: src/Atrium/Tabs.php, src/Atrium/TabDefinition.php, src/Atrium/TabVisibility.php, src/RosterServiceProvider.php, phpunit.xml.dist, tests/Pest.php, tests/Unit/TabDefinitionTest.php, tests/Unit/TabsTest.php
criteria: 1, 2, 3, 4, 5, 18

- [x] 1.1 Write tests: add a `Unit` testsuite (`tests/Unit`, on the Roster `TestCase`) and a `Tabs::flush()` `beforeEach`/`afterEach` for `Feature/Ui` in `tests/Pest.php`; unit tests for `TabDefinition` (defaults, fluent setters, view/component clearing each other, readonly-from-outside `Error`, private `resolver`/`authorize`, `public()`) and `Tabs` (singleton binding, `organization()`/`user()` return the registry, key pattern accepted/rejected incl. `acme.billing`, uppercase, space, slash, empty, all-digit `123`/`007` read back as strings, every `ORGANIZATION_TABS` key and `overview` rejected with the key in the message, duplicate key message names key and screen, missing view/component rejected, clone on registration, `flush()`, registration with `roster.ui.enabled` off)
- [x] 1.2 Implement `Atrium\TabDefinition` (final, `public private(set)` state, `resolveData()`, `allows()` through `ScreenAccess::allows()` then the closure, a throwing closure denied and reported through the memo), `Atrium\Tabs` (`ORGANIZATION_TABS`, `USER_DEFAULT`, `organization()`, `user()`, `organizations()`, `users()`, `flush()`, `(string)` keys) and `Atrium\TabVisibility` (memo keyed by screen, record key and tab key, plus the reported-key set); bind `Tabs` as a singleton and `TabVisibility` with `scoped()` in `RosterServiceProvider::register()`
- [x] 1.3 Run composer test:unit; all green

## Group 2: Shared tab bar and organization screen tabs
depends on: 1
files: src/Atrium/Http/Controllers/OrganizationUiController.php, resources/views/ui/organizations/show.blade.php, resources/views/ui/partials/tabs.blade.php, resources/views/ui/partials/registered-tab.blade.php, tests/Feature/Ui/OrganizationTabBarTest.php, tests/Feature/Ui/OrganizationRegisteredTabsTest.php, tests/Unit/OrganizationTabsTest.php
criteria: 3, 6, 7, 8, 9, 10, 14, 15, 16

- [x] 2.1 Write the characterization test first (`OrganizationTabBarTest`: exact built-in `data-testid`s in order and each `?tab=` `href`, plus the bar shows exactly the visible keys of `Tabs::ORGANIZATION_TABS`) and confirm it passes against today's markup; then write `OrganizationRegisteredTabsTest` (order after built-ins with a hidden middle tab, escaped label, unknown-icon fallback matching `squares-2x2` markup, resolver counting for every `?tab=` case incl. `?tab[]=x`, Roster variables win over resolver data, component `data` prop with `slot`/`attributes`/dashed keys, screen authorization before host closures, default stays built-in/`settings`, permission/closure/`public()`/neither matrix with bar and 403, ungranted permission hidden, throwing `authorize` reported once with `Exceptions::fake()` and run once, no closures with nothing registered, two sequential requests with `forgetScopedInstances()`, throwing resolver propagates, all-digit keys reach the view as strings, deleted organization ignores `?tab=` with zero closure calls) and the unit test that the controller's private permission map keys equal `Tabs::ORGANIZATION_TABS`
- [x] 2.2 Extract the organization nav into `roster::ui.partials.tabs` (built-in markup unchanged, `aria-current` from `x-atrium::icon-button`, registered labels/icons escaped, `Icons::has()` fallback) and add `roster::ui.partials.registered-tab` (`@include` with resolver data first and Roster's keys last, or `x-dynamic-component` with a single `data` prop)
- [x] 2.3 Update `OrganizationUiController::show()`: string-only `tab`, private key => permission map keyed by `Tabs::ORGANIZATION_TABS`, default from visible built-ins before appending registered keys, visibility computed once through `TabVisibility`, 403 for hidden registered keys, unknown keys fall back to the default, resolver only for the open tab after the access check, deleted page untouched
- [x] 2.4 Run composer test:unit; all green (existing organization feature tests unmodified)

## Group 3: User screen tabs and overview
depends on: 2
files: src/Atrium/Http/Controllers/UserUiController.php, resources/views/ui/users/show.blade.php, resources/views/ui/users/partials/overview.blade.php, tests/Feature/Ui/UserPagesTest.php, tests/Feature/Ui/UserRegisteredTabsTest.php, tests/Feature/Ui/SharedTabBarTest.php
criteria: 7, 9, 10, 11, 12, 13, 14, 15, 16

- [x] 3.1 Write tests: run the existing `UserPagesTest` suite as a dataset over "no tab registered" and "one visible tab registered" (only tab-bar assertions differ); `UserRegisteredTabsTest` (no `data-testid="tab-` when none visible, `?tab={hidden}` 403, `?tab=overview` / empty / unknown / `?tab[]=x` render the unchanged page, overview default and holds all content, registered tab gets `user` and resolver data, resolver only for the open tab after access, screen authorization before host closures, permission/closure/`public()`/neither matrix with `ScreenAccess::allows($p, null, $user)`, throwing `authorize` reported once, `DB::listen` proves `Tables::name('sso_identities')` is read on the overview and not on a registered tab, redirects carry no `tab=` and land on the overview, deleted user ignores `?tab=` with zero closure calls); `SharedTabBarTest` (same fixture keys on both screens give the same `data-testid` set and only the open tab carries `aria-current="page"`)
- [x] 3.2 Move the current user page content unchanged into `users/partials/overview` and render the shared tab bar only when a user tab is visible
- [x] 3.3 Update `UserUiController::show()`: string-only `tab`, visible user tabs through `TabVisibility`, unchanged page when none, skip the overview's data when a registered tab is open, 403 for hidden keys, unknown keys fall back to `overview`, redirects unchanged
- [x] 3.4 Run composer test:browser; all green
- [x] 3.5 Run composer test:unit; all green

## Group 4: Documentation
depends on: 1
files: README.md, resources/boost/skills/roster-development/SKILL.md, CHANGELOG.md, tests/Feature/DocsTest.php
criteria: 17

- [x] 4.1 Write the documentation test: inside the `Registered tabs` section of the README and the Boost skill (heading to the next heading of equal or higher level), assert `Tabs::class`, `vendor.key` and `boot()` appear
- [x] 4.2 Write the README `Registered tabs` section (registration example for both screens from `boot()`, `vendor.key`, new built-in keys in minor releases, `public()` warning, cheap non-throwing `authorize` closures that must not assume GET, resolver/view errors surface as page errors, missing view/component fails only on render, reserved view variable names, published `roster-views` need re-publishing), the matching Boost skill section, and a CHANGELOG upgrade note naming the Atrium version that provides `Icons::has()` and the published-views caveat
- [x] 4.3 Run composer test:unit; all green
