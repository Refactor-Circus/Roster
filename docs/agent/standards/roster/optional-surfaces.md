---
description: "Roster's optional surfaces and their flags: roster.routes.enabled (the JSON API, and the public invitation lookup with its own lookup_middleware), roster.mcp.web.enabled, roster.mcp.local.enabled; all off by default"
tags: [roster, config, surfaces, http, mcp]
kind: convention
---

# Optional Surfaces

Complements [[service-providers]] (optional surfaces behind a strict `=== true` config flag) with Roster's own surfaces and where their wiring comes from.

## Roster's surfaces

| Surface | Flag (default) | Wiring from |
|---|---|---|
| JSON API | `roster.routes.enabled` (`false`) | `roster.routes.prefix`, `roster.routes.middleware` |
| Public invitation lookup | `roster.routes.enabled` (`false`) | `roster.routes.prefix`, `roster.invitations.lookup_middleware` (never `roster.routes.middleware`) |
| MCP web transport | `roster.mcp.web.enabled` (`false`) | `roster.mcp.web.route`, `roster.mcp.web.middleware` |
| MCP local transport | `roster.mcp.local.enabled` (`false`) | `roster.mcp.local.handle` |

- Every surface is off until the host turns it on, because each needs host authentication middleware Roster cannot choose.
- The public invitation lookup is part of the API: it is registered only while `roster.routes.enabled` is `true`, but with its own middleware, because its caller is not signed in yet. Its default middleware keeps the `throttle:roster-invitation-lookup` limiter; never move it into `roster.routes.middleware`.
- The signed web routes (the invitation page behind `roster.invitations.middleware`, impersonation, the signed transfer-file link) are not optional surfaces: they follow the links Roster emails or returns.
- Roster has no screens to switch on; a frontend is the host's choice and calls the JSON API.
- A new optional surface is a `roster.<surface>.enabled` flag with the same shape, off by default.
