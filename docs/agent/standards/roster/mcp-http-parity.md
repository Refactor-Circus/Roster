---
description: "MCP structured content must equal the HTTP payload"
tags: [roster, mcp, http, testing, parity]
kind: convention
---

# MCP/HTTP Parity Tests

Every MCP tool that returns a resource has a parity test: its structured content equals the HTTP payload for the same record.

## Rules

- HTTP is the canonical shape; MCP must match. This is the executable check on the shared-resource contract ([[resources]], [[mcp-responses]]).
- Required for every mutation tool. List tools also test the empty case (`data: []` must not error).
- Invoke tools through `RosterServer::tool(ToolClass::class, $args)`; never construct requests by hand.

```php
it('creates a team with parity to the http payload', function () {
    $mcp = RosterServer::tool(CreateTeamTool::class, [...])->assertOk();

    $http = $this->getJson(route('roster.organizations.teams.show', [...]))->json('data');

    $mcp->assertStructuredContent($http);
});
```
