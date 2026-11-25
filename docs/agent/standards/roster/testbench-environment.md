---
description: "Roster's pinned Testbench environment: FK constraints on, provider order (MCP, Impex, Roster), migration paths, API mounted, mode TestCases per tests/ folder, KeenTestCase only for the audit log, per-test config"
tags: [roster, testing, testbench, pest]
kind: convention
---

# Testbench Environment

Complements [[test-layers]] with what Roster's `tests/TestCase.php` pins. Don't undo these.

## Pinned

- `database.default` is `testing` with `foreign_key_constraints` on, so FK violations fail in tests.
- Providers, in order: `McpServiceProvider`, `ImpexServiceProvider`, then `RosterServiceProvider`. No other package's provider is registered, and `TestCase` keeps the dev siblings' own screen registration off (their `enabled` flags under `impex.*` and `cortex.*`).
- Migrations: Testbench's defaults, `workbench/database/migrations`, the package's `database/migrations`, and the dev dependencies' migrations (`refactor-circus/impex`, `refactor-circus/keen`).
- `roster.routes.enabled` is on, so the JSON API and the public invitation lookup are mounted; `tests/Defaults` covers the shipped defaults, where it is off.
- Surface tests run with `roster.authorization` off; `tests/Authorization` turns it back on through `AuthorizationTestCase` ([[authorization]]).

## Modes

- Each mode is a `TestCase` subclass bound to its own folder in `tests/Pest.php` (`uses(KeenTestCase::class)->in('Modes/Keen')`): `Authorization`, `Cortex`, `Defaults`, `Modes/Keen`, `Modes/Owned`, `Modes/RenamedTables`, `Modes/Traitless`, `Modes/TransfersUnavailable` and `Modes/Ulid`.
- refactor-circus/keen is booted only by `KeenTestCase` (`tests/Modes/Keen`), which covers what Roster teaches the audit log. Everywhere else no audit log is installed.
- There is no browser suite: what a frontend sees is the HTTP payload, covered by feature tests under `tests/Feature/Http`, and the API description by `tests/Feature/OpenApiTest.php`.

## Per-test config

- Call `config()->set(...)` at the start of the test, or add a mode `TestCase` subclass. Never change the shared `TestCase` for one feature.
