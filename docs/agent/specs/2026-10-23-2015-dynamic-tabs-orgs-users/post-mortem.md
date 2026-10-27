# Post-mortem: Registered tabs on organization and user screens

## Summary

Spec `2026-10-23-2015-dynamic-tabs-orgs-users` (issue #4, PR #5, merged) lets host apps register tabs on Roster's Atrium organization and user screens. It adds `Tabs`, `TabDefinition` and `TabVisibility` in `src/Atrium`, a shared tab-bar partial, and a user `overview` wrapper. The final result was approved with no findings in round 2. The cost was not in review. It went to three infrastructure failures (one prepare failure, two build escalations) and a gate that could not run two required commands in round 1. No human PR review comments were recorded.

## Metrics

- Decisions: 24 (D-001 to D-024). Spec review took one round with four revisions plus two last questions (Q-023, Q-024).
- Rounds: 2 (round 1 CHANGES, round 2 PASS).
- Escalations: 3 (prepare failed, group 2 no output, group 3 no output).
- Runs: 4 entries across 2 workflow runs (`wf_8bb54b4a-e0f`, `wf_b9c34c78-9d1`).
- Deviations: 4 recorded in `decision.md`, plus smaller ones in `build.md`.
- Findings: none from the code, security or standards reviewers in round 2. Round 1 produced 2 gate blockers, both "command not run".
- Final gate: `composer test` 1239 passed, `composer test:browser` 14 passed, lint and `ringside check` passed.
- PR review comments from people: 0.

## What went well

- The spec was heavily pressure-tested before build (`spec-review.md`). Four revisions fixed ambiguity on timing, memoisation, unknown icons, default-deny, and redirects. That left the implementation with no reviewer findings.
- Tests came first and were checked. `OrganizationTabBarTest` was run against the pre-refactor code and then against the extracted partial. `UserRegisteredTabsTest` failed 54 of 56 with the changes set aside.
- Strong behavioural tests: `DB::listen` for the overview-only table, counting closures and resolvers, `forgetScopedInstances()` between viewers, and `Exceptions::fake()` for reporting.
- Deviations were written down in `build.md` as they happened.
- Resumed runs recovered uncommitted work without redoing groups 1 and 4.

## What cost rounds

- Run 1 (2026-10-23) failed in prepare: Bash was denied to subagents, so no worktree was created. This cost a full restart on 2026-10-25.
- Groups 2 and 3 each ended without structured output, leaving work uncommitted. Each needed a resume.
- Round 1 gate returned CHANGES only because it could not run `vendor/bin/ringside check` (permission denied in don't-ask mode) and `composer test:browser`. It mapped criteria by file name only.
- The rework had to run both commands. `ringside check` could not run inside the worktree, because Ringside isn't in the worktree `vendor/` and the runner refused the main checkout's binary. It ran from the main checkout and relied on a diff showing no config changes.
- A flaky test in `tests/Feature/Ui/RosterPluginTest.php` (random owner "Carmel Kling" matched the "ada" search) failed once. It was fixed outside the job's file list.
- Round 2 gate did not run `composer test:browser` itself, although criterion 15 and the spec's done definition include it. It accepted the rework's result.

## Root causes

- **tooling**: Subagents were denied Bash in don't-ask mode, which broke the prepare step and the round 1 gate's `ringside check`. Evidence: `meta.yml` run 1, `round-1/gate.md`.
- **tooling**: Implementer agents returned no structured output for groups 2 and 3. Evidence: `meta.yml` runs 2 and 3, `decision.md` build notes. The two largest groups (controller plus view refactor) failed. This suggests output or time limits on big groups.
- **tooling**: `ringside check` reads `getcwd()` and Ringside isn't installed in worktrees, so it can't run where the code lives. Evidence: `build.md` rework round 1.
- **standards_gap**: `testing/test-layers` says `tests/Unit` is framework-free, but this spec needed container, config and `ScreenAccess` there. The deviation was never added to `docs/agent/standards-feedback.md`. Likewise, `testbench-environment` says surface tests run with authorization off, but visibility tests need it on.
- **spec_gap**: The spec referenced `roster.ui.enabled`, which does not exist in `config/roster.php`. Criterion 5 became a test of a nonexistent key. The spec also said "gate includes `composer test:browser`" while `composer test` excludes the Browser suite. This was caught late.
- **spec_gap**: Group 2 and 3 file lists missed `lang/en/roster.php` (the `overview` label) and fixtures folder placement. These caused small deviations.
- **implementer_error** (minor): the flaky `RosterPluginTest` was a random-data collision, not introduced by this job but exposed by it.
- **reviewer_noise** (gate round 1): the gate marked criteria "not verified one by one" and mapped by file name only. Round 2 did the same. Neither read the tests.

## What human review caught

No human reviewer comments were recorded on PR #5. The only human input was the ticket-comment decisions (D-001 to D-024) and the Q-023/Q-024 answers. Those happened before build and shaped the design (default-deny, private closures, 403 on hidden tabs).

## Proposed follow-ups

- Make Bash (or an allow-list for `composer` and `vendor/bin/*`) available to subagents in prepare and gate, or have the orchestrator run those commands.
- Make `ringside check` accept a project path argument, or install Ringside in worktrees.
- Split large build groups (controller plus view plus tests) or make a missing structured output recoverable by committing partial work automatically.
- Add a gate rule that `composer test:browser` runs when the spec's done definition includes it, in every round.
- Record the two testing-standard deviations in `docs/agent/standards-feedback.md`, and clarify whether `tests/Unit` may boot the app.
- Spec check: verify config keys named in criteria exist (here `roster.ui.enabled`).
- Fix test data in `RosterPluginTest` style tests with fixed names and emails, to avoid random collisions.
