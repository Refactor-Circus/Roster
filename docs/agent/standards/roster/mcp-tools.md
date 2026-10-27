---
description: "Declarative tool shells; logic in McpRequest classes mirroring FormRequests"
tags: [roster, mcp, tools, requests]
kind: convention
---

# MCP Tools

MCP tools are declarative shells: all logic lives in an `*McpRequest` class beside them, the MCP mirror of the HTTP request's `persist()`.

## Layout

- Tools live in `src/Domains/{Domain}/Mcp/Tools/`, their requests in `src/Domains/{Domain}/Mcp/Requests/*McpRequest.php`.
- Tools extend `RefactorCircus\Keystone\Mcp\Tool`, which serves Cortex's published description override.

## Tool

- `handle()` only returns `$request->persist()`. Never put queries or action calls in `Tool::handle()`.

```php
#[Description('Create a team inside an organization.')]
final class CreateTeamTool extends Tool
{
    public function handle(CreateTeamMcpRequest $request): Response|ResponseFactory
    {
        return $request->persist();
    }

    public function schema(JsonSchema $schema): array { ... }
}
```

## Request

- `rules()` returns the identifier rules (see [[mcp-model-binding]]) plus `{Action}Data::rules()`, as the HTTP request does ([[data-objects]]); `handle(array $validated)` builds the input with `{Action}Data::fromValidated($validated)` and calls the action. Same mental model and test shape on both surfaces as [[request-persist]].
- The base `RefactorCircus\Roster\Mcp\Request` extends `RefactorCircus\Keystone\Mcp\Requests\Request`, whose final `persist()` runs authorize, validate and not-found handling inside the `mcp` surface. Never re-implement that in a tool.

## Schema and rules stay in step

- `schema()` and `rules()` describe the same fields; change both together. The schema is what the model sees, the rules are what is enforced. See [[mcp-schemas]].
