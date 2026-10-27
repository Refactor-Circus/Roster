---
description: "Every Roster action is reachable from the HTTP API, MCP and the Atrium UI; a gap is listed in PARITY_EXCEPTIONS with its reason"
tags: [roster, actions, surfaces, http, mcp, atrium, parity]
kind: convention
---

# Action Surfaces

Complements [[test-layers]] (`Parity::gaps()`) with Roster's surfaces: every action is offered on the HTTP API, the MCP server and the Atrium UI.

## Rules

- A new operation starts with its action, then gets an HTTP request ([[request-persist]]), an MCP request and tool ([[mcp-tools]]) and an Atrium screen or controller.
- An action a surface cannot meaningfully offer goes in `PARITY_EXCEPTIONS` in `tests/Pest.php` with the surfaces it skips (`http`, `mcp`, `ui`) and a comment saying why (a browser-only sign-in, a machine-to-machine sync).
- `tests/ArchTest.php` asserts `parityGaps()` is empty; never silence it by widening the scanned paths.
