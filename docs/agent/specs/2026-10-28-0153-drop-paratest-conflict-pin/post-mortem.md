# Post-mortem: Drop the paratest conflict pin

Spec `2026-10-28-0153-drop-paratest-conflict-pin` · ticket [#7](https://github.com/Refactor-Circus/Roster/issues/7) · PR [#8](https://github.com/Refactor-Circus/Roster/pull/8) (merged 2026-10-28) · post-mortem run `wf_8afe7b25-97d`

## Metrics

| Rounds | Decisions | Deviations | Escalations | Findings |
| --- | --- | --- | --- | --- |
| 1 | 2 | 1 | 0 | none |

## Root causes

- **Tooling**: `composer.lock` is gitignored, so the worktree has no lock. Composer refused the spec's partial update (`composer update pestphp/pest brianium/paratest --with-all-dependencies`). The implementer ran a full `composer update` instead, which also bumped laravel/framework, phpunit, phpstan and the refactor-circus siblings (keystone, atrium, cortex, impex, keen, pennantplus, ringside) to newer dev-main. Nothing beyond the planned change was committed, but the gate passed against a dependency set nobody planned. (build.md Deviations; spec.md Design)
- **Spec gap**: criterion 5 ("same test count as before the change") had no measured baseline. The build prompt said not to run the suite, and the gate noted "pre-change count not independently available". The 1239 figure came from an earlier job's gate. (build.md, round-1/gate.md, spec.md criterion 5)
- **Implementer error (minor)**: a retry of the build found commit 6a68411 already done. It only re-verified the checks. (build.md Retry)

## Human review delta

- No human PR or ticket review comments; the ticket holds only D-001, D-002 and the build-start note. No delta to attribute.
- Gate gap: criterion 5 can't be verified without a recorded baseline count.
- Gate gap: the gate ran on drifted sibling dev-main versions that aren't in the diff, so its pass proves less than the spec implies.

## Proposals (awaiting your call)

The session ran unattended, so these have not been accepted or rejected yet. Run `/ring-master-post-mortem 2026-10-28-0153-drop-paratest-conflict-pin` to go through them. The full text is in the workflow result for `wf_8afe7b25-97d`.

1. `roster/dependency-updates` (tooling, project): in dependency-change specs, plan `composer update --dry-run` and then a full `composer update`, since there's no lock to update partially. Record the resolved set (targeted packages, framework/tooling bumps, each sibling's dev-main commit) in `build.md`. Never commit the lock.
2. `roster/test-count-baselines` (convention, project): a criterion that compares to "before the change" names a measured baseline (count, command, `main` commit), taken when the spec is written. Without one, the gate marks the criterion unverified, not passed.

## Rejected proposals

None yet.
