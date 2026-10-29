# Build: Drop the paratest conflict pin

## Group 1

Commit `6a68411` on `ring-master/2026-10-28-0153-drop-paratest-conflict-pin`.

### Files

- `composer.json`: removed the `conflict` block (`"brianium/paratest": ">=7.26.1"`) and the trailing comma left on `repositories`; `pestphp/pest` `^5.3.1` -> `^5.3.4`. `git diff --stat`: `composer.json | 7 ++-----`, 1 file changed, 2 insertions, 5 deletions.

### Tests

There are no new tests. This is a constraint-only change, and the criteria are checked by commands:

- Criterion 1: `"conflict"` count in `composer.json` was 1 before and is 0 after.
- Criterion 2: `"pestphp/pest": "^5.3.4"`.
- Criterion 3: `composer show brianium/paratest` gives `v7.27.4`. `composer why brianium/paratest` gives `pestphp/pest v5.3.4 requires brianium/paratest (^7.27.4)`, along with testbench-core's existing `<7.3.0|>=8.0.0` conflict, which allows it. `composer.lock` is still gitignored and not committed.
- Criterion 4: `composer validate` gives `./composer.json is valid`.
- Criterion 5: I did not run it. The build prompt said not to run the suite because the gate runs `composer test` after the build. No fresh baseline count was measured. The last recorded count on main is 1239 passed (`composer test`, from the gate of 2026-10-23-2015-dynamic-tabs-orgs-users). Since then main has only had the Ringside migration and the Cortex namespace commits.
- Criterion 6: the diff is exactly the two hunks above.

### Deviations

- Task 1.3 ran a full `composer update` instead of `composer update pestphp/pest brianium/paratest --with-all-dependencies`. The worktree has no `composer.lock` (it is gitignored), so Composer refuses a partial update ("Cannot update only a partial set of packages without a lock file present"). Copying the main checkout's lock into the worktree was not permitted in this session. A dry run first confirmed that resolution needs no change to the pest plugins or other constraints. The full update changed only the untracked lock and the worktree's `vendor/`:
  - pest 5.3.1 -> 5.3.4, paratest 7.26.0 -> 7.27.4, phpunit 13.4.1 -> 13.4.5
  - laravel/framework 13.38.0 -> 13.39.0, laravel/mcp 1.1.1 -> 1.1.2, phpstan 2.3.1 -> 2.3.3, larastan 3.13.1 -> 3.13.2
  - minor patch bumps
  - refactor-circus siblings at newer dev-main: keystone 3b48908, atrium c51068c, cortex f650849, impex 66de976, keen 27bb597, pennantplus 4886869, ringside 952f2da

  The spec's risks section already expects these transitive bumps. If the gate fails, check whether a sibling dev-main bump rather than pest or paratest caused it before applying the failure path.

### Retry

On the retry I re-checked commit `6a68411` and found nothing left to change or commit. The worktree is clean and `composer.lock` is untracked. The checks still pass: `composer validate` says valid; the `"conflict"` count is 0; paratest is `v7.27.4`; and `composer why` gives `pestphp/pest v5.3.4 requires brianium/paratest (^7.27.4)`. The diff against `main` is still just the two `composer.json` hunks. Per the build prompt I did not run the suite (criterion 5); the gate runs it.
