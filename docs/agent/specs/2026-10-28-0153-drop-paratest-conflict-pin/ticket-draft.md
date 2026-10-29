# Drop the paratest conflict pin

## Problem

`composer.json` carries a temporary `"conflict": {"brianium/paratest": ">=7.26.1"}` entry that works around a paratest regression. pest ^5.3.4 requires paratest ^7.27.4, which fixes it, so the pin is no longer needed. Keystone already did this in its PR #8.

## Desired outcome

- The `conflict` entry is removed from `composer.json` (the `conflict` key too, if it ends up empty).
- `pestphp/pest` is required at `^5.3.4`.
- `composer update` for pest and paratest has run and `composer.lock`, if tracked, reflects it.

## Acceptance hints

- `composer test` passes, including a `--parallel` run.
- No other change to `composer.json` or the code.

## Out of scope

- Any other dependency, config or code change.
- Other packages in campaign #1 (Atrium, Cortex, Impex, PennantPlus, Polycart, Showroom).
