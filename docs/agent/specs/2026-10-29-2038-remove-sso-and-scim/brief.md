Risk: high

## What changed

- **Domains removed:** `src/Domains/Sso` and `src/Domains/Scim` are deleted. That covers their providers, actions, models, policies, events, routes, the 10 MCP tools, `DescribesSsoConnection` and the factory.
- **Atrium UI:** `SsoUiController`, `ScimUiController` and `resources/views/ui/sso` are gone. The organization screen keeps the tabs `members`, `teams`, `invitations`, `roles` and `settings`. The user overview loses its SSO identities card.
- **Config and data:** the `sso` and `scim` config sections and the five table keys are removed. Migrations 000006 and 000007 are deleted, and the SSO/SCIM entries are dropped from the index migrations. Nothing drops, renames or alters tables or permission rows.
- **Composer:** `suggest` now points to `refactor-circus/guardian`. The four SSO vendor packages leave `suggest` and `require-dev`.
- **Sign-in rule:** `NotSsoEnforced` is replaced by `Support\Rules\NoExternalSignIn`, with message key `external_sign_in_required`.
- **Docs:** the CHANGELOG has a Breaking entry, and the README, the Boost skill, CLAUDE.md, AGENTS.md and the standards docs are updated.
- **Tests:** the SSO/SCIM tests are deleted, and new tests are added.

The diff also contains the seams and Atrium screen-registry work, because this branch is stacked on those two specs.

## Why

Guardian (`refactor-circus/guardian`) now owns SSO and SCIM. Roster should no longer name them anywhere in source, config, routes, permissions, tables or vendor libraries, and existing installs must lose no data (D-001, D-004).

- **D-010:** deleting `SsoConnectionModel` opens Guardian's handover gate. Apps that installed Guardian early run `guardian:upgrade-from-roster`.
- **D-011/D-014:** a stored `scim` membership source is shown as its raw value.
- **D-012/D-015:** the generic `NoExternalSignIn` rule stays in Roster.
- **D-013/D-016/D-018:** apps with published migration copies rely on a bold CHANGELOG step, which a DocsTest asserts.
- **D-017:** without Guardian, SSO-enforced organizations accept password sign-in. This fail-open is accepted and documented prominently.

## Look at

- `CHANGELOG.md` ([Unreleased] > Breaking, "SSO and SCIM moved to refactor-circus/guardian"): the upgrade steps and the fail-open warning. Check that the step order and the bold "before any migrate" step are correct.
- `src/Support/Rules/NoExternalSignIn.php:1`: the replacement for `NotSsoEnforced`. Check the message key and that behaviour is unchanged.
- `database/migrations/2026_01_01_000010_add_roster_lookup_indexes.php` and `2026_10_12_000001_add_roster_performance_indexes.php`: only the SSO/SCIM entries are removed. Confirm nothing else changed.
- `composer.json` (`suggest` and `require-dev`): the four packages are dropped, `require` is untouched, and the Atrium `conflict` is kept.
- `src/Atrium/Http/Controllers/OrganizationUiController.php`: the tab list, the permission map and the `?tab=sso` fallback to `members`.

## Tested

The round 2 gate passed: `composer test` ran 1286 tests with 0 phpstan errors and 100% coverage, and `composer lint:check` and `ringside check` passed.

- **Criteria 1, 2, 5-9, 12:** `tests/Feature/RemovedSsoScimTest.php`, plus `ArchTest`.
- **Criterion 2 (Keen):** `tests/Modes/Keen/KeenAuditTest.php`.
- **Criterion 3:** `tests/Feature/Permission/PermissionSyncTest.php`.
- **Criterion 4:** `tests/Feature/Organization/MembershipSourceTest.php` and `tests/Unit/Organization/MembershipSourcesTest.php`.
- **Criterion 10:** `tests/Feature/LegacySsoScimUpgradeTest.php`, using the `tests/Fixtures/legacy_sso_scim_schema.php` fixture.
- **Criterion 11:** `tests/Feature/ComposerTest.php`.
- **Criterion 13:** `tests/Feature/NoExternalSignInTest.php`.
- **Criteria 14, 15:** `tests/Feature/DocsTest.php`.
- **Criterion 16:** the browser suite is a manual gate and was not run. The SSO/SCIM browser tests were deleted.

## Risks

- **Fail-open (accepted, D-017):** upgrading Roster without Guardian turns SSO and SCIM off at once. Enforced-SSO users can then sign in with a password, and identity providers get 404s. There is no code guard, only the bold warning in the CHANGELOG and README.
- **Published migrations (D-013):** an app that kept published copies of migrations 000006/000007 or the index migrations will throw on the missing `Tables` keys. It could also recreate empty tables if it does not follow the CHANGELOG step.
- **Early Guardian installs:** they must run `guardian:upgrade-from-roster`.
- **Breaking consumers:** every `Domains\Sso|Scim` class, route name and permission name is removed from Roster's public surface.
- **Manual gates:** the browser suite and `composer validate --strict` were not run.
- Open findings: none (nits: 0).
