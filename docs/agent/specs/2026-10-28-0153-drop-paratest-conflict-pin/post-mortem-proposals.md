# Pending post-mortem proposals

Full text from post-mortem run `wf_8afe7b25-97d`. Accept, edit or reject each with `/ring-master-post-mortem 2026-10-28-0153-drop-paratest-conflict-pin`.

## 1. `roster/dependency-updates` (target: project, kind: tooling)

**Rationale:** Composer refused the planned partial update because the worktree has no lock (`composer.lock` is gitignored, `.gitignore` line 6). The implementer fell back to a full `composer update`, which also bumped laravel/framework, phpunit, phpstan and seven refactor-circus siblings, none of which is in the diff. If the spec plans the full update and the build records the resolved set, the gate's pass is tied to versions you can name. This doesn't overlap with the global architecture/dependencies standard, which covers what goes in `require`.

**Evidence:** spec.md Design (partial update command); build.md Deviations; tasks.md 1.3 ticked with a command that never ran; `.gitignore` line 6.

````md
---
description: "Dependency-change specs in Roster: no composer.lock in a worktree, so plan a full composer update and record the resolved set the gate tests against"
tags: [roster, composer, dependencies, tooling]
kind: tooling
---

# Dependency Updates

`composer.lock` is gitignored (`.gitignore` line 6), so a fresh worktree has no lock. A spec that changes dependencies plans for that, and the build records the versions the gate ran against.

Complements [[dependencies]], which covers what may go in `require`. This standard covers how a constraint change gets resolved and verified.

## Planning

- Do not plan a partial update (`composer update vendor/pkg --with-all-dependencies`). Without a lock, Composer refuses it ("Cannot update only a partial set of packages without a lock file present").
- Plan `composer update --dry-run` first. It shows whether resolution needs other constraint changes, such as pest plugin bumps. If it does, follow the spec's failure path. Then plan a full `composer update`.
- Say in the spec that the full update also moves the refactor-circus siblings (keystone, atrium, cortex, impex, keen, pennantplus, ringside) to their current dev-main. Those moves are expected and are never committed.
- Never commit `composer.lock`. The proof of a version floor is the constraint in `composer.json`, plus `composer show` / `composer why` output.

## Recording

- In `build.md`, list what the update changed: the packages the spec targets, any framework or tooling bumps (laravel/framework, phpunit, phpstan, larastan), and each refactor-circus sibling with its resolved dev-main commit.
- If the gate fails, first check whether a sibling dev-main bump caused it, not the targeted package. Only then apply the failure path.

## Example

```md
### Resolved set (untracked lock)

- pestphp/pest 5.3.1 -> 5.3.4, brianium/paratest 7.26.0 -> 7.27.4
- laravel/framework 13.38.0 -> 13.39.0, phpstan 2.3.1 -> 2.3.3
- siblings at dev-main: keystone 3b48908, atrium c51068c, cortex f650849
```
````

## 2. `roster/test-count-baselines` (target: project, kind: convention)

**Rationale:** Criterion 5 asked for "the same test count as before the change", but no baseline was measured. Task 1.1 ("Record the baseline") is ticked, yet the build never ran the suite. The 1239 figure came from an earlier job's gate, and the gate passed the criterion while noting "pre-change count not independently available". Measuring the baseline when the spec is written avoids this, and having the gate show both numbers stops an unverifiable criterion from reading as passed.

**Evidence:** spec.md criterion 5; tasks.md 1.1; build.md criterion 5; round-1/gate.md criterion 5; spec-review.md round 1.

````md
---
description: "Criteria that compare test counts to 'before the change' must name a measured baseline: count, command and main commit"
tags: [roster, testing, specs, gate]
kind: convention
---

# Test Count Baselines

An acceptance criterion that compares the test count to "before the change" names the baseline: the count, the command that produced it, and the `main` commit it was measured on.

## When writing the spec

- Measure the baseline on `main` while writing the spec: run `composer test:unit` and put the passed count in the criterion. The build and the gate then compare against a fixed number. Neither has to re-run the suite before the edit.
- A count copied from an earlier job's gate is not a baseline unless it names that job and the `main` commit, and nothing that adds or removes tests has landed since.
- If you cannot measure a baseline, do not write "same count as before". Write a threshold you can check, such as "exits 0 with at least N passed", and explain where N came from.

## In the build and the gate

- Do not tick a "record the baseline" task that was not run. If the build skips it, the deviation goes in `build.md`.
- The gate reports both numbers, baseline and after. If there is no baseline, the gate marks the criterion unverified, not passed.

## Example

```md
5. `composer test` exits 0 with a parallel run and 1239 passed, the same as the
   baseline (`composer test:unit` on main 89ac9cf, measured 2026-10-28).
   The browser suite is excluded.
```
````
