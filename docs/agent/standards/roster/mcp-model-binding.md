---
description: "Per-model abstract McpRequest bases resolve identifier to model"
tags: [roster, mcp, requests, slugs]
kind: convention
---

# MCP Model Binding

Slug-to-model resolution lives in an abstract per-model `{Entity}McpRequest` base, the MCP analog of route-model binding.

## Rules

- Create the per-model base for any slug-to-model lookup, even for a single tool: consistency over YAGNI.
- Memoize with `??=`, since rules and `handle()` may both need the model.
- Use `firstOrFail()`. The base `persist()` catches `ModelNotFoundException` and returns `Response::error('Not found.')`, so no manual existence checks.
- Concrete requests add the slug argument (`['required', 'string']`) to their rules alongside the action's rules.

```php
abstract class TeamMcpRequest extends OrganizationMcpRequest
{
    private ?TeamModel $resolvedTeam = null;

    protected function team(): TeamModel
    {
        return $this->resolvedTeam ??= TeamModel::query()
            ->where('slug', $this->get('team'))
            ->firstOrFail();
    }
}
```

See [[mcp-tools]] and [[stored-identifiers]].
