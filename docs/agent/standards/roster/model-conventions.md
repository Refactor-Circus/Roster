---
description: "Roster models: HasUlids on every ULID-keyed model, explicit $fillable (never $guarded = [])"
tags: [roster, models, eloquent, ulid]
kind: convention
---

# Model Conventions

Complements [[models]] with Roster's key trait and mass-assignment rule.

## Rules

- Every model on a ULID-keyed table ([[ulid-keys]]) uses `HasUlids`. ULIDs avoid id collisions when records move between environments by export and import, and don't leak row counts; the slug stays the public identity ([[stored-identifiers]]).
- The exceptions keep integer keys and no `HasUlids`: `TransferRowModel` (internal rows, unique by `transfer_id` and `line`) and the opt-in `UserModel` (its `users` migration uses `$table->id()`, like Laravel's default users table).
- Every model lists its mass-assignable columns in `$fillable`. Never `$guarded = []`.
