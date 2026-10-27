# Build: Registered tabs on organization and user screens

## Group 1

Tab registry, definition and per-request memo. Commit `4e29ba7` on `ring-master/2026-10-23-2015-dynamic-tabs-orgs-users`.

### Files

- `src/Atrium/TabDefinition.php`: final class. `public readonly string $key` is validated against `^[a-z0-9][a-z0-9._-]*$` in the constructor, and the exception message names the key. `public private(set)` holds `label` (defaults to the key), `icon` (`squares-2x2`), `view`, `component`, `permission` and `public`. `resolver` and `authorize` are private closures. The fluent setters are `label()`, `icon()`, `view()` / `component()` (setting one clears the other), `resolve()`, `permission()`, `authorize()` and `public()`. `resolveData(Model)` returns `[]` when there is no resolver and lets a throwing resolver propagate. `allows(Request, Model $record, ?OrganizationModel $scope, ?Model $self)` hides the tab by default and returns `public` when there is neither a permission nor a closure. Otherwise it checks the permission through `ScreenAccess::allows($permission, $scope, $self)`, then the closure, where only `=== true` passes. A throwing closure counts as denied and goes to `TabVisibility::report()`.
- `src/Atrium/Tabs.php`: final class. It holds `ORGANIZATION_TABS` (keys only, in today's controller order), `USER_DEFAULT = 'overview'`, `organization()`, `user()` (both return `$this`), `organizations()`, `users()` and `flush()` (tests only). Registration clones the definition and validates the clone. It rejects reserved keys and missing view/component, and both messages name the key. It also rejects duplicates with a message that names the key and the screen. Keys are read back with `(string)`. `organizations()` / `users()` hand out clones, so a listed definition cannot change the registry either.
- `src/Atrium/TabVisibility.php`: final, bound with `scoped()`. `allows(string $screen, TabDefinition, Request, Model $record, ?OrganizationModel $scope, ?Model $self)` remembers each answer by screen, record key and tab key. `report(string $key, Throwable)` calls `report()` once per request per tab key. This is the API groups 2 and 3 use from the controllers.
- `src/RosterServiceProvider.php`: `register()` binds `singleton(Tabs::class)` and `scoped(TabVisibility::class)`.
- `phpunit.xml.dist`: adds the `Unit` testsuite (`tests/Unit`).
- `tests/Pest.php`: `uses(TestCase::class)->in('Unit')`, plus `beforeEach` / `afterEach` hooks that call `app(Tabs::class)->flush()` for `Feature/Ui`.
- `tests/Unit/TabDefinitionTest.php`, `tests/Unit/TabsTest.php`.

### Tests

- `TabDefinitionTest` covers:
  - defaults and fluent setters
  - view and component clearing each other
  - `Error` when assigning any property from outside or reading `resolver` / `authorize`
  - key rejection on construction
  - `resolveData()`: empty without a resolver, receives the record, a throwing resolver propagates
  - `allows()`: default-deny, `public()`, the closure receiving the request and record, permission through ScreenAccess (pass with authorization off, guest denied with it on), both-must-pass with the closure skipped when the permission fails, non-`true` treated as denied
  - a throwing closure is denied and reported once per request per key, and reported again after `forgetScopedInstances()`
  - the memo: one closure run per screen, record and key; separate answers per record, screen and key; rebuilt after `forgetScopedInstances()`; a throwing closure runs once and is reported once
- `TabsTest` covers:
  - the singleton binding, the scoped memo binding, and `organization()` / `user()` returning the registry
  - registration order
  - accepted keys, including `acme.billing`
  - rejected keys (uppercase, space, slash, empty, leading dot or dash, other characters) on both screens, with the registry left empty
  - `123` / `007` read back as strings
  - `ORGANIZATION_TABS` pinned, with every key rejected and the key named in the message
  - `overview` rejected on the user screen, and reserved keys reserved only on their own screen
  - duplicates: the message names the key and the screen, and the first registration is kept; the same key is allowed on both screens
  - missing view/component rejected
  - a clone kept at registration and clones handed out on read
  - `flush()`
  - registration with `roster.ui.enabled` off: a valid tab is stored and an invalid one throws
- `composer test:unit`: 1131 passed. `composer analyse`, `composer lint:check` and `composer test:types` (100%) also pass.

### Deviations

- testing/test-layers says `tests/Unit` is framework-free. Following the task, the new `Unit` suite runs on Roster's `TestCase`, because the criteria test the container bindings (singleton, scoped memo), config and `ScreenAccess`. This is recorded here and has not been added to `docs/agent/standards-feedback.md`, which this group does not own.
- `roster.ui.enabled` is not a key in `config/roster.php`; the UI is switched off through Atrium (`atrium.disabled`). The criterion 5 test sets `roster.ui.enabled` to false and shows that registration never reads it.
- `Tabs::organizations()` / `users()` return clones of the stored definitions, beyond the spec's clone at registration. This keeps the registry unchangeable through a listed definition. The API and types are unchanged.

## Group 4

Documentation. Commit `304938e` on `ring-master/2026-10-23-2015-dynamic-tabs-orgs-users`.

### Files

- `tests/Feature/DocsTest.php`: adds `markdownSection($markdown, $title)`. It returns the text under the first heading named `$title`, optionally numbered (`### 13. Registered tabs`), up to the next heading of the same or a higher level, and it skips `#` lines inside fenced code blocks. Two new tests use it:
  - a self-test on inline Markdown: mentions before and after the section don't count, a deeper heading and a fenced `# comment` stay inside, numbered headings match, and a missing heading gives null
  - a dataset over `README.md` and `resources/boost/skills/roster-development/SKILL.md`, asserting the section exists and contains `Tabs::class`, `vendor.key` and `boot()`
- `README.md`: a new `### Registered tabs` section at the end of `## Atrium dashboard`. It covers:
  - a registration example for both screens from `boot()`
  - registering only in `boot()` (not `register()`, middleware or controllers), lazy reads, the clone kept at registration, and `flush()` for tests only
  - the key pattern, reserved keys (`Tabs::ORGANIZATION_TABS`, `overview`), duplicates naming key and screen, new built-in keys in minor releases, and the `vendor.key` advice
  - `permission()`, `authorize()`, both, and default-deny, plus a `public()` warning
  - the screen's own check running first, 403 for hidden tabs, and unknown keys falling back to the default
  - `authorize` closures: cheap, non-throwing, not assuming GET, and reported once when they throw
  - placement on both screens and the user `overview`, with deleted records ignoring tabs
  - escaped labels and the icon fallback
  - the resolver running only for the open tab after access
  - view variables, with the reserved names `organization`/`user`, `tab`, `tabs`, `registeredTab`, `errors`, `__env`, `app` and `loop`
  - the component's record prop and single `data` prop
  - resolver, view and component errors surfacing as page errors, and a missing view or component failing only on render
  - re-publishing `roster-views`
- `resources/boost/skills/roster-development/SKILL.md`: `### 13. Registered tabs` under Workflow, with the same promises in short, plus an anti-pattern: don't publish views just to add a tab.
- `CHANGELOG.md`: an Unreleased "Added" entry for registered tabs, with two "Upgrading" notes:
  - Atrium version: Atrium has no tagged release, and Roster requires it as `dev-main`. `Icons::has()` arrived in Atrium `main` commit `f5eb488` (2026-10-02), which also brought `icon-button`, so the note names that commit.
  - Published views: copies published with `roster-views` get no registered tabs. Re-publish, or merge the `ui/partials/tabs`, `ui/partials/registered-tab` and `ui/users/partials/overview` views.

### Tests

- Before the docs were written, `composer test:unit -- tests/Feature/DocsTest.php` failed: both dataset cases reported no Registered tabs heading. After: 5 passed.
- `composer test:unit`: 1171 passed. That run includes group 2's uncommitted work in the worktree. `composer lint:check` passes.

### Deviations

- The README doesn't mention `roster.ui.enabled`, because that key doesn't exist in `config/roster.php` (see group 1). It says instead that registration works whether or not Atrium shows Roster, and that tabs only render on Roster's Atrium screens.
- The README and skill describe the component path as getting the record (`organization` / `user` prop) plus one `data` prop. The record prop comes from group 2's in-progress `registered-tab` partial; the spec only fixes the `data` prop. If group 2 changes the partial, update this sentence.
- The CHANGELOG names an Atrium commit, not a version number, because Atrium has no tags.

## Group 2

Shared tab bar and organization screen tabs. Commit `88e2134` on `ring-master/2026-10-23-2015-dynamic-tabs-orgs-users`.

### Files

- `resources/views/ui/partials/tabs.blade.php`: the shared tab bar, a plain partial that gets every key it reads: `label`, `tabs`, `tab`, `icons`, `registered` and `href`.
  - `label` names the `<nav>`. `tabs` lists the keys in order and `tab` is the open one.
  - `icons` maps each built-in key to its icon. `registered` maps keys to `TabDefinition`s. `href` is a closure that turns a key into its `?tab=` link.
  - Built-in tabs render the same `x-atrium::icon-button` markup as before. `aria-current="page"` marks the open tab, and `data-testid="tab-{key}"` stays the test hook.
  - A registered tab takes its label and icon from its definition, both escaped through `{{ }}`. An icon name that `Icons::has()` doesn't know falls back to `squares-2x2`.
- `resources/views/ui/partials/registered-tab.blade.php`: the open registered tab's body.
  - It gets `registeredTab`, `data` (the resolver's output) and `roster`, which holds exactly Roster's five variables: the record (`organization` / `user`), `tab`, `tabs`, `registeredTab` and `errors`.
  - On the view path it renders `@include($view, [...$data, ...$roster])`, so Roster's keys win.
  - On the component path it renders `x-dynamic-component` with the record as an `organization` / `user` prop and the resolver's output as one `data` prop. This matches what the group 4 README already says.
- `resources/views/ui/organizations/show.blade.php`: the nav is replaced by `@include('roster::ui.partials.tabs', ...)`, which passes the built-in icon map unchanged. A registered tab's body comes before the built-in `@elseif` chain.
- `src/Atrium/Http/Controllers/OrganizationUiController.php`: `show()` changes in these ways:
  - The private `TABS` is renamed to `TAB_PERMISSIONS`, a key => permission map. The built-in bar is built by filtering `Tabs::ORGANIZATION_TABS` through it.
  - `tab` is read as a string or null.
  - The default (`$tabs[0] ?? 'settings'`) is chosen from the visible built-in tabs before any registered key is added.
  - Registered visibility is computed once through `TabVisibility::allows('organization', ...)`, with the organization as both record and scope, and only after the screen's own authorization. The bar and the `?tab=` check share that one result.
  - A registered key the viewer can't see aborts 403. An unknown key falls back to the default.
  - The resolver runs only for the open registered tab, after its access check.
  - The deleted branch still returns before any tab logic.
  - The view also receives `registeredTabs`, `registeredTab` and `registeredData`.
- `tests/Feature/Ui/OrganizationTabBarTest.php`, `tests/Feature/Ui/OrganizationRegisteredTabsTest.php`, `tests/Unit/OrganizationTabsTest.php`.

### Tests

- `OrganizationTabBarTest` (characterization) checks:
  - the exact built-in `data-testid`s, in order, and the `href` of each `?tab=` link
  - that the bar shows exactly the keys of `Tabs::ORGANIZATION_TABS` that are offered, and with a restricted viewer exactly the ones that viewer may see
  - that only the open tab carries `aria-current="page"`
  
  It was run against the pre-refactor view and controller (stashed): 4 passed. It then passed unchanged against the extracted partial.
- `OrganizationRegisteredTabsTest` covers:
  - order after the built-ins, with a hidden middle tab (`tab-a` / `tab-c` / no `tab-b`, after `tab-settings`)
  - an escaped label
  - the unknown-icon markup matching a `squares-2x2` tab's markup
  - the view rendering the organization and resolver data, with the open tab marked `aria-current`
  - resolver counting (a dataset over the registered tab, `members`, no tab, a hidden tab with 403, an unknown key, `?tab[]=x` and `?tab[a]=b`)
  - Roster's variables winning over the resolver's `organization` / `tab` / `tabs` / `registeredTab` / `errors`
  - the component `data` prop with `slot`, `attributes` and `data-key`
  - zero closure or resolver calls for a viewer denied the screen (403 and 404)
  - the default staying `settings` when only a registered tab is visible
  - the permission / closure / `public()` / neither matrix (11 cases, each checking the bar and `?tab=` 403), including an ungranted permission and one nothing grants
  - a throwing `authorize`: run once, reported once, 403 on `?tab=`, then 200 on the default tab without the tab in the bar
  - no closures running when nothing is registered on this screen
  - two sequential viewers with `forgetScopedInstances()` between them
  - a throwing resolver propagating
  - `123` / `007` reaching the view as strings (strict closure in `assertViewHas`)
  - a deleted organization ignoring `?tab=` with zero closure calls
- `tests/Unit/OrganizationTabsTest.php`: checks by reflection that the private `TAB_PERMISSIONS` keys equal `Tabs::ORGANIZATION_TABS`.
- Results:
  - `composer test:unit`: 1171 passed. The existing organization feature tests and `StylesTest` pass unmodified.
  - `composer analyse`, `composer lint:check` and `composer test:types` (100%) pass.

### Deviations

- `composer test:browser` (including the unmodified `tests/Browser/OrganizationsBrowserTest.php`) could not run in the worktree because Playwright isn't installed there (`PlaywrightNotInstalledException`). Task 3.4 runs the browser suite.
- testbench-environment says surface tests run with `roster.authorization` off. The spec places the visibility and denial tests in `tests/Feature/Ui`, so those tests switch authorization on per test with `config()->set('roster.authorization', true)`. This follows the standard's "Per-test config" rule rather than moving the tests to `tests/Authorization`.
- The host fixture view and anonymous component are written to a per-process temp directory and registered with `View::addNamespace` / `Blade::anonymousComponentPath`, not kept in `tests/Fixtures/`. That folder isn't in this group's file list.
- `@include` also passes along the including view's variables. A host view therefore also sees the partial's own variables (`data`, `roster`) and the show view's variables, but only underneath the resolver data and Roster's five keys, which always win.

## Group 3

User screen tabs and overview. Commit `b82a78d` on `ring-master/2026-10-23-2015-dynamic-tabs-orgs-users`.

### Files

- `src/Atrium/Http/Controllers/UserUiController.php`: `show()` now takes the `Request`.
  - `tab` is read as a string or null.
  - After the screen's own authorization and the deleted branch, the visible user tabs are computed once through `TabVisibility::allows('user', ...)`, with the user as the record and as `self` and no scope. That makes the permission check `ScreenAccess::allows($p, null, $user)`. The bar and the `?tab=` check share that one result.
  - A registered key the viewer can't see aborts 403. `overview`, an empty value, an unknown key or a non-string value opens the overview.
  - `tabs` is `[]` when no user tab is visible, so the page is unchanged. Otherwise it is `overview` followed by the visible keys.
  - The resolver runs only for the open registered tab, after its access check.
  - The overview's data (memberships, current context, assignments, effective permissions, assignable roles, SSO identities, redirect domains) moved into a private `overview()` method. It is loaded only while the overview is open. No loader seam was added.
  - Redirects are unchanged.
- `resources/views/ui/users/partials/overview.blade.php`: the whole former page body, moved unchanged (re-indented only). That includes the impersonation banner, flash, Actions, history, impersonation card and danger zone.
- `resources/views/ui/users/show.blade.php`: keeps the page header. It renders the shared `roster::ui.partials.tabs` bar (with `overview` using the `user-circle` icon) only when `tabs` isn't empty. It then renders either the registered tab through `roster::ui.partials.registered-tab`, with the banner and flash above it, or the overview partial.
- `lang/en/roster.php`: adds `'overview' => 'Overview'`. This file is outside the group's file list. The shared bar labels built-in tabs with `roster::roster.{key}`, so the `overview` tab needs this key.
- `tests/Feature/Ui/UserPagesTest.php`, `tests/Feature/Ui/UserRegisteredTabsTest.php`, `tests/Feature/Ui/SharedTabBarTest.php`.

### Tests

- `UserPagesTest`: every test runs over the `userTabStates()` dataset ("no tab registered" and "one visible tab registered", a `public()` tab). The existing assertions are unchanged. Only `userTabBar()` differs: no `data-testid="tab-` in the first state, and `tab-overview` then the registered tab, with `overview` open, in the second.
- `UserRegisteredTabsTest` (56 cases) covers:
  - the page unchanged while no user tab is visible, compared as markup against a baseline render with the bar stripped. This runs over nothing registered, a tab with neither check, a tab its closure refuses, and a tab registered only on the organization screen, crossed with no `?tab=`, `overview`, empty, an unknown key and `?tab[]=x`
  - hidden keys answering 403 even with no bar
  - `overview` as the default, holding all of the page's content (the same markup as the baseline), with only it carrying `aria-current`
  - the registered view getting the user and the resolver's data
  - resolver counts over the registered tab, overview, no tab, empty, hidden (403), unknown, a list and a map
  - Roster's variables winning over the resolver's
  - the component's single `data` prop with `slot`, `attributes` and a dashed key
  - zero closure or resolver calls when the screen is denied (403) or the user is missing (404)
  - a 13-case permission / closure / `public()` / neither matrix, checking both the bar and `?tab=`. It includes an ungranted permission, one nothing grants, one held only in an organization (hidden, because the user screen asks globally), and a self-permission on the viewer's own page (shown, which proves the user is passed as `self`)
  - a throwing `authorize`: run once and reported once in a request that renders the bar and checks `?tab=`, then 403, then 200 on the overview
  - no closures running when tabs are registered only on the organization screen
  - two sequential viewers with `forgetScopedInstances()` between them, re-evaluated and reported once each
  - a throwing resolver propagating
  - `DB::listen`: `Tables::name('sso_identities')` is read on the overview (the control) and not on a registered tab
  - redirects: a profile save and a failing status post carry no `tab=`, no closure runs for either, and the first redirect lands on the overview
  - `123` / `007` staying strings
  - a deleted user ignoring `?tab=` with zero closure calls
- `SharedTabBarTest`: one fixture of two tabs is registered on both screens. One tab has a markup label and an unknown icon. The tests check:
  - the same registered `data-testid`s, in the same order, after each screen's built-ins
  - identical link markup apart from the `href`
  - only the open tab carrying `aria-current="page"` for each registered tab
  - each screen's default (`members` / `overview`) as the only current tab
- Tests-first check: with the controller and `show.blade.php` changes set aside, `UserRegisteredTabsTest` failed 54 of 56. With them restored, it passed.
- `composer test:unit`: 1239 passed. `composer test:browser`: 14 passed, after running `npm ci` in the worktree to install the locked Playwright (no tracked files changed). `composer lint:check` and `composer analyse` pass.

### Deviations

- `lang/en/roster.php` was changed outside the group's file list (see Files).
- As in group 2, the tests that check visibility and denial switch `roster.authorization` on per test, in `tests/Feature/Ui`, following testbench-environment's per-test config rule.
- Host fixture views and components are written to a per-process temp directory, as in group 2.

## Rework round 1

Fixes the gate's blocker: `vendor/bin/ringside check` and `composer test:browser` hadn't been run. Commit `9de154d` on `ring-master/2026-10-23-2015-dynamic-tabs-orgs-users`.

### Fixes

- `composer test:browser` in the worktree: 14 passed.
- `vendor/bin/ringside check`: passed. Ringside isn't installed in the worktree's `vendor/`, and it reads the project from `getcwd()`, so the check ran from the main checkout. The runner refuses a call to the main checkout's binary from the worktree, so it couldn't run there. The checker reads only Ringside config, standards, generated agent files, `docs/agent/standards` and `pint.json`. `git diff --name-only main...HEAD -- .claude .agents AGENTS.md CLAUDE.md docs/agent pint.json composer.json composer.lock` is empty, so this branch can't change the check's result.
- `composer test:unit` failed once in the worktree on `RosterPluginTest` "searches users and organizations as separate atrium sources", which this branch doesn't touch. The organization's random owner (`Carmel Kling`) matched the user search for "ada". The test now gives the owner a fixed name and email, so it can't match by chance. This file is outside the job's file list.

### Tests

- `composer test:unit`: 1239 passed. `composer test:browser`: 14 passed. `composer lint:check` passes.
