---
description: "Roster's pinned Testbench environment: FK constraints on, provider order, migration paths, mode TestCases per tests/ folder, KeenTestCase only for the audit log, per-test config"
tags: [roster, testing, testbench, pest]
kind: convention
---

# Testbench Environment

Complements [[test-layers]] with what Roster's `tests/TestCase.php` pins. Don't undo these.

## Pinned

- `database.default` is `testing` with `foreign_key_constraints` on, so FK violations fail in tests.
- Providers, in order: `McpServiceProvider`, `AtriumServiceProvider`, `ImpexServiceProvider`, then `RosterServiceProvider`.
- Migrations: Testbench's defaults, `workbench/database/migrations`, the package's `database/migrations`, and the dev dependencies' migrations (`refactor-circus/impex`, `refactor-circus/keen`).
- Surface tests run with `roster.authorization` off; `tests/Authorization` turns it back on through `AuthorizationTestCase` ([[authorization]]).

## Modes

- Each mode is a `TestCase` subclass bound to its own folder in `tests/Pest.php` (`uses(KeenTestCase::class)->in('Modes/Keen')`): `Authorization`, `Defaults`, `Modes/Ulid`, `Modes/RenamedTables` and the rest.
- refactor-circus/keen is booted only by `KeenTestCase` (`tests/Modes/Keen`), which covers what Roster teaches the audit log. Everywhere else no audit log is installed.

## Per-test config

- Call `config()->set(...)` at the start of the test, or add a mode `TestCase` subclass. Never change the shared `TestCase` for one feature.
