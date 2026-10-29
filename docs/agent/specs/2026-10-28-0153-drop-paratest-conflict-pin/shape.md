# Drop the paratest conflict pin — Shaping Notes

## Scope

Maintenance change to `composer.json` only (ticket #7).

- Remove the temporary `"conflict": {"brianium/paratest": ">=7.26.1"}` entry; drop the `conflict` key as well, since it would be empty.
- Raise `pestphp/pest` from `^5.3.1` to `^5.3.4`, which requires paratest `^7.27.4` (the fixed release).
- Run `composer update pestphp/pest brianium/paratest --with-all-dependencies` locally. `composer.lock` is gitignored and not committed; the `^5.3.4` floor is the guard.
- No other dependency, config or code change. Other campaign packages are out of scope.

## Decisions

- D-001 (ticket stage): create the GitHub issue as drafted. Done: issue #7.
- Scope stays minimal: no other `composer.json` edits (e.g. the `pest-plugin-*` constraints) unless the update itself forces them.

## Context

- Reference: Keystone did the same in its PR #8.
- No product docs are affected; this is tooling only.
- Verification: `composer test`, including a `--parallel` run.

## Standards

- `architecture/dependencies`: governs how Composer constraints are declared; the only standard that touches this change.
