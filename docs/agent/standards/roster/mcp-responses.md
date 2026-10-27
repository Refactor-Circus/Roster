---
description: "Structured responses use data envelope and shared HTTP Resources"
tags: [roster, mcp, responses, resources]
kind: convention
---

# MCP Responses

Every structured MCP response wraps its payload in a `data` envelope, serialized through the same resources as the HTTP API ([[resources]]).

## Envelope

```php
// lists
return $this->structuredCollection(TeamResource::collection($teams)->resolve());
// → { "data": [ ... ] }

// single items: same envelope
return Response::structured(['data' => (new TeamResource($team))->resolve()]);
```

- The envelope is mandatory for lists: `Response::structured([])` throws, so an empty list ships as `{ "data": [] }`. That is why `structuredCollection()` exists.
- Some older single-item responses return the bare resource with no envelope. That is legacy: wrap it in `data` when touched, don't copy it.

## Errors

- Errors use `Response::error('...')`; the base request maps `ModelNotFoundException` to `Not found.` ([[mcp-model-binding]]).
