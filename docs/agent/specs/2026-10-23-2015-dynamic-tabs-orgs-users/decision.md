# Decision

Status: **approved** (factory run `wf_b9c34c78-9d1`, 2 rounds, 2026-10-25)

## Reasons

- Gate green in round 2: `composer test` (1239 passed), `composer test:browser` (14 passed), `composer lint:check`, `vendor/bin/ringside check`.
- Round 2 reviewers (code, security, standards) returned no findings.
- Round 1 gate failed only because the gate agent couldn't run `ringside check` / the browser suite; rework round 1 ran both and fixed a flaky `RosterPluginTest` (random owner name matching the "ada" search).

## Build notes

- Groups 2 and 3 each needed a resumed run: their first implementer ended without returning a structured result, leaving work uncommitted; the resumed agent finished and committed it.

## Deviations (from build.md)

- `tests/Unit` runs on Roster's `TestCase` (testing/test-layers says framework-free); the criteria test container bindings and `ScreenAccess`.
- `roster.ui.enabled` is not a config key; criterion 5's test sets it and shows registration never reads it, and the README doesn't mention it.
- CHANGELOG names Atrium `main` commit `f5eb488` for `Icons::has()` (Atrium has no tags).
- `tests/Feature/Ui/RosterPluginTest.php` changed outside the file list to fix a flaky test.

## Nits

None.
