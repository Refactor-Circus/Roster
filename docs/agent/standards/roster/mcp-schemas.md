---
description: "Schema field descriptions and shared payload traits"
tags: [roster, mcp, schemas]
kind: convention
---

# MCP Schemas

Every MCP schema field carries a `->description()`, because it is the model-facing documentation of the tool.

## Field descriptions

- State defaults and behavior ("Defaults to true.", "Replaces the whole list."): the model can't read the code.

```php
'slug' => $schema->string()->description('URL identifier, unique within the organization. Omit to generate one.'),
'name' => $schema->string()->description('Display name.')->required(),
```

## Shared fragments

- A schema fragment used by two or more tools goes into a shared `Describes*` trait (e.g. `DescribesOrganization`, `DescribesProfilePayload`) so tools never drift.

## Tool descriptions

- Tool descriptions use the `#[Description('...')]` attribute. Keep it accurate: it is the model's only documentation of the tool. See [[mcp-tools]].
