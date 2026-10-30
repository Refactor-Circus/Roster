# Tasks: Adopt Atrium screen registry

## Group 1: Remove other packages' UI from Roster screens
package: Roster
depends on: none
files: src/Atrium/RedirectDomains.php, src/Atrium/Http/Controllers/RedirectDomainUiController.php, src/Atrium/Http/Controllers/OrganizationUiController.php, src/Atrium/Http/Controllers/UserUiController.php, src/Atrium/RosterPlugin.php, resources/views/ui/partials/redirect-domains.blade.php, resources/views/ui/organizations/show.blade.php, resources/views/ui/users/partials/overview.blade.php, resources/views/ui/roles/show.blade.php, resources/views/ui/teams/show.blade.php, lang/en/roster.php, tests/Cortex/RedirectDomainsTest.php, tests/Feature/Ui/RedirectDomainPagesTest.php, tests/Feature/Ui/AuditTrailTest.php, tests/Feature/HistoryNavigationTest.php, tests/Authorization/ScreenControlsTest.php, tests/Modes/Keen/OrganizationActivityRemovedTest.php, tests/Feature/Ui/RemovedIntegrationsTest.php, tests/ArchTest.php
criteria: 7, 8, 11

- [ ] 1.1 Write tests for criteria 7, 8 and 11: in `tests/Modes/Keen` no `tab-activity` and `?tab=activity` answers 200 on the default tab; `ScreenControlsTest` activity 403 becomes 200 and its history test is deleted; `Route::has()` false for the four `*.redirect-domains.store|destroy` routes; the four translation keys (`mcp`, `redirect_domains`, `redirect_domains_hint`, `no_redirect_domains`) are gone; arch test that `RefactorCircus\Cortex` is not used in `RefactorCircus\Roster`; file-content scans for `redirect-domains`/`RedirectDomain`, `historyNavItem`/`audit-trail` in `src` and `resources` and `x-atrium::audit` in `resources/views`; `RosterPlugin::navigation()` has no `atrium.history*` item
- [ ] 1.2 Remove `OrganizationUiController::offers()`, the `mcp`/`activity` branches, icons and permission-map entries, `ListRedirectDomainsAction` and `Roster::auditing()` uses; delete `RedirectDomains`, `RedirectDomainUiController`, the redirect-domains partial and card, its four routes in `RosterPlugin` and the four translation keys; remove every `<x-atrium::audit-trail>` and the `historyNavItem()` call; delete `tests/Cortex/RedirectDomainsTest.php`, `RedirectDomainPagesTest.php`, `AuditTrailTest.php` and `HistoryNavigationTest.php`
- [ ] 1.3 Run vendor/bin/pest; all green

## Group 2: Declare every screen on Atrium's registry
package: Roster
depends on: 1
files: src/Atrium/Tabs.php, src/Atrium/TabDefinition.php, src/Atrium/TabVisibility.php, src/Atrium/ScreenAccess.php, src/Atrium/Http/Controllers/Concerns/AuthorizesScreens.php, src/Atrium/Http/Controllers/UserUiController.php, src/Atrium/Http/Controllers/OrganizationUiController.php, src/Atrium/Http/Controllers/TeamUiController.php, src/Atrium/Http/Controllers/RoleUiController.php, src/Atrium/Http/Controllers/PermissionUiController.php, src/Atrium/Http/Controllers/ImpersonationUiController.php, src/Atrium/Http/Controllers/TransferUiController.php, src/RosterServiceProvider.php, resources/views/ui/partials/tabs.blade.php, resources/views/ui/partials/registered-tab.blade.php, resources/views/ui/users/index.blade.php, resources/views/ui/users/create.blade.php, resources/views/ui/users/show.blade.php, resources/views/ui/users/partials/overview.blade.php, resources/views/ui/organizations/index.blade.php, resources/views/ui/organizations/create.blade.php, resources/views/ui/organizations/show.blade.php, resources/views/ui/teams/show.blade.php, resources/views/ui/roles/index.blade.php, resources/views/ui/roles/show.blade.php, resources/views/ui/permissions/index.blade.php, resources/views/ui/impersonations/index.blade.php, resources/views/ui/transfers/index.blade.php, resources/views/ui/transfers/show.blade.php, tests/Pest.php, tests/ArchTest.php, tests/Unit/TabDefinitionTest.php, tests/Unit/TabsTest.php, tests/Unit/OrganizationTabsTest.php, tests/Feature/Ui/ScreenDeclarationsTest.php, tests/Feature/Ui/ScreenAccessOnTest.php, tests/Feature/Ui/ScreenOrderOfEvaluationTest.php, tests/Feature/Ui/ScreenRegistryResetTest.php, tests/Authorization/ScreenRegistryResetTest.php, tests/Feature/Ui/OrganizationTabBarTest.php, tests/Feature/Ui/OrganizationRegisteredTabsTest.php, tests/Feature/Ui/UserRegisteredTabsTest.php, tests/Feature/Ui/SharedTabBarTest.php, tests/Feature/Ui/UserPagesTest.php
criteria: 1, 2, 3, 4, 5, 6, 9, 10

- [ ] 2.1 Write tests for criteria 1 to 6, 9 and 10 (criterion 6's reserved-key case for keys the viewer may not open is Group 5): `ScreenDeclarationsTest` dataset over the 13 screens and their slots; `ScreenAccessOnTest` with one allowed/denied case per record row, null organization and null requester, eager-loading query counts and the `aside` and `row.actions` permission sections; the refused-viewer dataset and deleted-record counting closures; the order-independent reset tests in `Feature` and `Authorization`; class/view absence and the file-content scan in `ArchTest`; rewrite `OrganizationTabBarTest`, `OrganizationRegisteredTabsTest`, `UserRegisteredTabsTest`, `SharedTabBarTest` and `UserPagesTest` to register through `Atrium::screens()->tab()` keeping every PR #5 case listed in criterion 10
- [ ] 2.2 Delete `Tabs`, `TabDefinition`, `TabVisibility`, both partials, their bindings and the three unit tests; add `ScreenAccess::allowsOn()` and the `screen()` helper on `AuthorizesScreens`; make each controller in the criterion 3 table build `Screen::standard()`, resolve after its own authorization, choose data by `$resolved->openTab` and pass `resolved`; render `x-atrium::screen-tabs`, `screen-tab-panel` and every declared slot in each view as Design describes; eager-load the list relations; apply `ResetsScreenExtensions` in `tests/Pest.php` behind `trait_exists()`
- [ ] 2.3 Run vendor/bin/pest and composer test:browser; all green

## Group 3: Atrium optional, with Roster's Atrium seam
package: Roster
depends on: 2
files: composer.json, composer.local.json, src/RosterServiceProvider.php, src/Support/Users.php, src/Atrium/RosterPlugin.php, src/Atrium/AtriumServiceProvider.php, src/Atrium/InvitationPage.php, src/Domains/Invitation/Contracts/InvitationPage.php, src/Domains/Invitation/Services/PlainInvitationPage.php, src/Domains/Invitation/InvitationServiceProvider.php, src/Domains/Invitation/Http/Controllers/InvitationWebController.php, resources/views/invitations/show.blade.php, resources/views/ui/invitations/show.blade.php, resources/views/impersonation-banner.blade.php, resources/views/ui/impersonation-banner.blade.php, resources/views/partials/impersonation-banner-plain.blade.php, tests/AtriumUnavailableTestCase.php, tests/TestCase.php, tests/Pest.php, tests/Modes/AtriumUnavailable/AtriumUnavailableTest.php, tests/Feature/AtriumSeamTest.php, tests/Feature/ComposerTest.php, tests/Feature/Ui/StylesTest.php, tests/Feature/Web/InvitationPageTest.php, tests/Feature/Web/ImpersonationFlowTest.php, tests/ArchTest.php, .github/workflows/tests.yml, docs/agent/standards-feedback.md
criteria: 12, 13, 14, 15, 16, 17

- [ ] 3.1 Write tests for criteria 12 to 17: `ComposerTest` (only Keystone in `require`, Atrium in `require-dev`, the three suggest strings, `conflict` `<0.1.0`); the three arch rules and the `RefactorCircus\Atrium` string scan outside `src/Atrium`; `AtriumUnavailableTest` (boot, no `atrium.roster.users.index`, API 201/200, `ListUsersTool`, plain invitation page and plain banner); `AtriumSeamTest` (Atrium invitation markup, `roster.invitation` declared with a `public()` aside section, Atrium banner, `@rosterCan` registered, `PlainInvitationPage` bound when `$withoutAtrium`); move `InvitationPageTest` and `ImpersonationFlowTest` markup assertions as the views move
- [ ] 3.2 Move Atrium to `require-dev`/`suggest` with the conflict and regenerate `composer.local.json`; add `Users::STATUS_COUNTS_CACHE`; add `AtriumServiceProvider` (string `class_exists` check plus `$withoutAtrium`), the `InvitationPage` contract, `PlainInvitationPage` and the Atrium `InvitationPage`; move `@rosterCan` out of `RosterServiceProvider`; split the invitation and banner views into plain and `ui/` versions with the dispatcher banner; exclude the two plain views in `StylesTest` and add the standards-feedback row; add `AtriumUnavailableTestCase` and its `Pest.php` mapping; add the `without-atrium` CI job (checking Keen's requirements)
- [ ] 3.3 Run vendor/bin/pest; all green

## Group 4: Documentation and merge gate
package: Roster
depends on: 3
files: README.md, resources/boost/skills/roster-development/SKILL.md, CLAUDE.md, CHANGELOG.md, tests/Feature/DocsTest.php, docs/agent/specs/2026-10-29-2038-adopt-atrium-screen-registry/meta.yml
criteria: 18, 19, 20, 21

- [ ] 4.1 Write tests for criteria 18 and 19: `DocsTest` checks "Screen extensions" in README and the Boost skill for every screen key, the built-in tab keys, `allowsOn` and the access mapping, `Atrium::screens()` and `aside`, drops the `Registered tabs` case, and checks the Unreleased CHANGELOG mentions `Tabs`, `?tab=activity`, `?tab=mcp`, `refactor-circus/atrium` and `conflict`
- [ ] 4.2 Replace "Registered tabs" with "Screen extensions" in README and the Boost skill, remove "MCP redirect domains", make Installation say Atrium is optional, update the package `CLAUDE.md`, write the CHANGELOG upgrade note, record the four blockers of criterion 21 in `meta.yml`, and draft the PR description listing them and every changed test by file and test name
- [ ] 4.3 Run vendor/bin/pest and composer test:browser; all green

## Group 5: Reserve every organization built-in key (blocked on Atrium `Screen::reserve()`)
package: Roster
depends on: 4
files: src/Atrium/Http/Controllers/OrganizationUiController.php, tests/Feature/Ui/OrganizationRegisteredTabsTest.php
criteria: 6

- [ ] 5.1 Write tests for criterion 6's reserved-key case: a viewer who may not open `roles` sees no `tab-roles` for a registered `roles` tab, `?tab=roles` answers 403, and the skip is reported once (`Exceptions::fake()`); this group starts only once Atrium ships `Screen::reserve()`
- [ ] 5.2 Call `reserve(...)` with all seven built-in keys when building `roster.organization`
- [ ] 5.3 Run vendor/bin/pest; all green
