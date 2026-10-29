# Drop the paratest conflict pin

Ticket: [#7](https://github.com/Refactor-Circus/Roster/issues/7)

## Goal

Remove the temporary `brianium/paratest` conflict pin from `composer.json`, now that `pestphp/pest ^5.3.4` requires the fixed paratest `^7.27.4`. Same change as Keystone's PR #8.

## Acceptance criteria

1. `composer.json` has no `conflict` key (`grep -c '"conflict"' composer.json` returns 0).
2. `composer.json` requires `pestphp/pest` at `^5.3.4`.
3. `composer.lock` is untracked (gitignored, `.gitignore` line 6) and must not be committed. The proof of the paratest floor is the `^5.3.4` constraint, since pest 5.3.4 requires paratest `^7.27.4`. Locally, `composer show brianium/paratest` shows `>=7.27.4` and `composer why brianium/paratest` shows `pestphp/pest` requires it.
4. `composer validate` passes.
5. `composer test` (lint:check, analyse, and test:unit, which already runs `pest --parallel --exclude-testsuite=Browser`) exits 0, and the output shows a parallel run with the same test count as before the change. The browser suite (`composer test:browser`) is excluded.
6. `git diff --stat` touches only `composer.json`, and its diff is exactly the removal of the `conflict` block and the pest constraint change.

## Design

Edit `composer.json`: delete the `conflict` block (taking care of the trailing comma on the preceding entry) and change `"pestphp/pest": "^5.3.1"` to `"^5.3.4"`. Run `composer update pestphp/pest brianium/paratest --with-all-dependencies`. No departures from `architecture/dependencies`.

Failure path: if resolution fails, needs a bump to the pest plugins (browser, laravel, type-coverage) or other constraints, or if `composer test` fails (type coverage or the parallel run) because of the new versions, stop and report. Do not widen scope or add code workarounds, and do not merge.

## Out of scope

- Any other dependency, config or code change.
- Other packages in campaign #1 (Atrium, Cortex, Impex, PennantPlus, Polycart, Showroom).

## Risks and open questions

- The update may pull other dev dependency bumps through `--with-all-dependencies`. They only affect the untracked lock and the local install, so they are not committed; if one forces a `composer.json` change, follow the failure path.
