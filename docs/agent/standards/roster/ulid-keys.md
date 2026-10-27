---
description: "Roster-owned tables use ULID primary keys and foreignUlid foreign keys"
tags: [roster, migrations, database, ulid]
kind: convention
---

# ULID Keys

Complements [[migrations]] with Roster's key type: Roster-owned tables use ULID primary keys and ULID foreign keys.

## Rules

- Primary keys are `$table->ulid('id')->primary()`. Exceptions: `transfer_rows` keeps `$table->id()` (internal rows, unique by `transfer_id` and `line`), and the opt-in `users` table keeps `$table->id()`. Columns pointing at host users follow `roster.users.key_type` through `UserKey::column()`, not `foreignUlid`.
- Models on these tables use `HasUlids` ([[model-conventions]]).
- Foreign keys to Roster tables are `foreignUlid(...)->constrained(Tables::name(...))` with an explicit cascade rule.
- Compound uniques where the domain demands them (`unique(['transfer_id', 'line'])`).
