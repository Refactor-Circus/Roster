---
description: "Roster's optional surfaces and their flags: roster.ui.enabled, roster.mcp.web.enabled, roster.mcp.local.enabled; the dashboard's path, middleware and gate come from Atrium's config"
tags: [roster, config, surfaces, mcp, ui]
kind: convention
---

# Optional Surfaces

Complements [[service-providers]] (optional surfaces behind a strict `=== true` config flag) with Roster's own surfaces and where their wiring comes from.

## Roster's surfaces

| Surface | Flag | Wiring from |
|---|---|---|
| Dashboard UI | `roster.ui.enabled` | Atrium's config (path, middleware, gate) |
| MCP web transport | `roster.mcp.web.enabled` | `roster.mcp.web.route`, `roster.mcp.web.middleware` |
| MCP local transport | `roster.mcp.local.enabled` | `roster.mcp.local.handle` |

- The dashboard's path, middleware and gate are Atrium's, never set in Roster's provider.
- A new optional surface is a `roster.<surface>.enabled` flag with the same shape.
