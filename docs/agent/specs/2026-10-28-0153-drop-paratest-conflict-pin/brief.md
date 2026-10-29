Risk: medium

## What changed

Only `composer.json` changed (1 file, 2 hunks). The `require-dev` constraint `pestphp/pest` went from `^5.3.1` to `^5.3.4`, and the temporary `"conflict": {"brianium/paratest": ">=7.26.1"}` block was removed. No source, config, migration or test changes.

## Why

The conflict pin was a stopgap for a paratest regression. Pest 5.3.4 requires the fixed paratest `^7.27.4`, so the floor on pest replaces the pin. This is the same change as Keystone's PR #8, and ticket #7 covers Roster. Decisions D-001 (create ticket as drafted) and D-002 (approve spec as written) kept scope to this one edit.

## Look at

- `composer.json:33`: the pest bump to `^5.3.4`. This is now the only guard that keeps paratest at or above 7.27.4.
- `composer.json:181`: the end of the `repositories` array, where the `conflict` block was removed. Check the JSON is still valid with no stray comma.

## Tested

- AC1 (no `conflict` key), AC2 (pest `^5.3.4`) and AC6 (diff limited to those two edits): shown by the diff.
- AC3: the gate report shows `brianium/paratest` v7.27.4 installed. `composer.lock` is untracked and not committed.
- AC4: `composer validate` is valid.
- AC5: `composer test` (pint, phpstan, parallel pest) passed with 1239 tests and 4349 assertions. The gate could not independently confirm the pre-change test count. The browser suite is excluded by design.
- Round 1 gate verdict: PASS.

## Risks

- `composer.json` is an architectural path and this is a dependency constraint change. It affects dev dependencies only, so it does not change what consumers install and breaks no packages.
- With the lock untracked, a developer or CI that resolves an older pest (below 5.3.4) is now blocked by the constraint. Nothing else prevents an older paratest, which is the intended trade-off.
- Open findings: none (nits: 0).
