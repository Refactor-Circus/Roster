---
description: "Every Roster action is reachable from the HTTP API and MCP; a gap is listed in PARITY_EXCEPTIONS with its reason; Roster has no screens, so a frontend reaches actions through the API"
tags: [roster, actions, surfaces, http, mcp, parity]
kind: convention
---

# Action Surfaces

Complements [[test-layers]] (`Parity::gaps()`) with Roster's surfaces: every action is offered on the HTTP API and the MCP server. Those are Roster's only surfaces; a frontend (a separate frontend package, or a host's own) reaches an action through the HTTP API, never through views or controllers of Roster's.

## Rules

- A new operation starts with its action, then gets an HTTP request ([[request-persist]]) on a route in its domain's `routes.php`, and an MCP request and tool ([[mcp-tools]]).
- What a frontend needs that no action answers yet (counts, a typeahead, an organization-wide list) becomes a read action with its own HTTP request and route, so it appears in `openapi.json` and the TypeScript client. Roster never renders it.
- An action a surface cannot meaningfully offer goes in `PARITY_EXCEPTIONS` in `tests/Pest.php` with the surfaces it skips (`http`, `mcp`) and a comment saying why (a browser-only sign-in, a public token lookup, a typeahead that the list tools already cover).
- `tests/ArchTest.php` asserts `parityGaps()` is empty; never silence it by widening the scanned paths.
- After adding or changing a route, regenerate the API description with `composer client:generate` and commit `openapi.json` and `client/` with it (`tests/Feature/OpenApiTest.php`).
