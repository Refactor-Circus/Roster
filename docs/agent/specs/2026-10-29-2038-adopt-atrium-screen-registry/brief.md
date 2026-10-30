Risk: high

## What changed
- Screens: all 13 dashboard list and detail screens (users, organizations, teams, roles, permissions, impersonations, transfers) now build `Screen::standard()` and resolve through Atrium's screen registry. Views render `x-atrium::screen-tabs`, tab panels and slot sections. New `ScreenAccess::allowsOn()` maps a record to its permission scope.
- Removed: Roster's own tab registry (`Tabs`, `TabDefinition`, `TabVisibility`, both partials), the `activity` and `mcp` organization tabs, the redirect-domains card, routes and controller, and all audit trails and the History nav item.
- Optional Atrium: `refactor-circus/atrium` moves to `require-dev` and `suggest`, with a `conflict` on `<0.1.0`. A new `Atrium\AtriumServiceProvider` is registered only when Atrium exists. It rebinds the invitation page (new `InvitationPage` contract, plain default) and registers `@rosterCan`. The impersonation banner dispatches between Atrium and plain views.
- Tests and CI: new `AtriumUnavailable` test mode, a `without-atrium` CI job, new arch, composer and docs tests, and rewritten PR #5 tab tests.
- Docs: README, Boost skill, `CLAUDE.md` and CHANGELOG upgrade notes.

## Why
Roster rendered other packages' UI and kept its own tab registry. Atrium's screen-extensions registry (PR #16) lets any package add tabs and sections, so Roster only declares screens. Decisions that shaped it:
- D-027, D-029 and D-032: the Roster-owned Atrium seam with plain fallback views.
- D-025: in-suite mode plus CI lane.
- D-028: one spec and one PR.
- D-030: the composer `conflict`.
- D-031: all seven built-in organization tab keys are reserved for every viewer.

## Look at
- `composer.json:47`: the `require` to `require-dev` move and the `conflict`. This is a dependency change for every consumer.
- `src/RosterServiceProvider.php`: the `class_exists` string check and `$withoutAtrium` gating of the seam.
- `src/Atrium/Http/Controllers/OrganizationUiController.php`: the built-in tab map, `reserve()` use, 403 versus default-tab handling, and data loaded only for the open tab.
- `src/Atrium/ScreenAccess.php` (`allowsOn`): the record-to-scope mapping, which decides what viewers see in other packages' sections.
- `resources/views/impersonation-banner.blade.php` and `resources/views/invitations/show.blade.php`: published view names now point at different markup.

## Tested
Round 3 gate: PASS. `composer test` passed (1271 tests, coverage 100%, phpstan and pint clean).
- Crit 1-2: `ArchTest`, `ScreenRegistryResetTest` (Feature and Authorization).
- Crit 3: `ScreenDeclarationsTest`.
- Crit 4: `ScreenAccessOnTest`.
- Crit 5: `ScreenOrderOfEvaluationTest`.
- Crit 6-7: `OrganizationTabBarTest`, `OrganizationRegisteredTabsTest`, `Modes/Keen/OrganizationActivityRemovedTest`, `ScreenControlsTest`.
- Crit 8 and 11: `RemovedIntegrationsTest`, `RosterPluginTest`.
- Crit 9-10: `UserPagesTest`, `UserRegisteredTabsTest`, `SharedTabBarTest`.
- Crit 12: `ComposerTest`.
- Crit 13: `ArchTest`.
- Crit 14 and 16: `AtriumUnavailableTest`, `AtriumSeamTest`, `InvitationPageTest`, `ImpersonationFlowTest`.
- Crit 18-19: `DocsTest`.
- Not run locally: the CI `without-atrium` job (crit 15 and 17) and `composer test:browser` (crit 20).

## Risks
- Merge gate (crit 21): do not merge before Atrium PR #16, the Atrium `Screen::reserve()` addition, Cortex `register-roster-redirect-domains` and Keen `history-section-by-capability`. Otherwise the activity, MCP, redirect-domain and history UI disappears with no replacement.
- Breaking public surface: removed classes and routes, `atrium.roster.*` routes unavailable without Atrium, and changed tab view variables and closure signatures. The CHANGELOG covers these.
- A `dev-main` Atrium from before PR #16 is not refused by the `conflict`.
- Apps that published `roster-views` keep old views until they re-publish.
- The plain invitation and banner views use inline styles, a standards departure recorded in `standards-feedback.md`.
- Open: nits: 1. The CI lane and browser suite were not verified here.
