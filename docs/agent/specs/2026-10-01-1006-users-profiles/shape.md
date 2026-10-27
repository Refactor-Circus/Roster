# Users + Profiles — Shaping Notes

## Scope

Phase 1 MVP, feature 1. User CRUD, 1:1 profiles, and an active/suspended/deactivated lifecycle, working on whatever User model the host app has. Every Action ships on all three surfaces (HTTP API, MCP, Atrium dashboard) in this slice.

## Decisions

- **Flexible user model (three modes)**, driven by `config('roster.users.model')`:
  1. Host model + `HasRoster` trait (default).
  2. Host model without the trait: the provider registers the `rosterProfile` relation dynamically via `resolveRelationUsing`.
  3. Roster-owned `RefactorCircus\Roster\Models\User` plus an opt-in `roster-users-migration` publish tag for apps without a users table.
- **Profiles in `roster_profiles` (1:1)**, never columns on the host users table. The `user_id` column type comes from `roster.users.key_type` (`int|ulid|uuid`). Profiles are created lazily; a missing profile means active.
- **Status: active / suspended / deactivated**, stored on the profile (`status`, `status_reason`, `status_changed_at`) so it works in every user mode. `roster.active` middleware blocks non-active users.
- **Column mapping** (`roster.users.columns`) lets Create/Update work on host schemas that don't use `name/email/password`.
- **Users referenced by route key, not slug.** Host users have no slug; this deviates from slug-references (exception recorded in that standard).
- **No authorization until feature 3 (roles).** Requests authorize `true`; route middleware is the only gate. To fail closed, HTTP routes and MCP default to **disabled**. Atrium stays behind Atrium's `viewAtrium` gate.
- **Surfaces per feature, not deferred.** Feature 4 shrinks to hardening (Cortex hookup, docs). The arch parity test checks HTTP + MCP + Atrium (stricter than Impex's MCP-only check).
- **Stack and conventions follow Impex/Cortex.** Generic Cortex standards adopted into `agent-os/standards/`.

## Context

- **Visuals:** None.
- **References:** Impex/Cortex Action + Request + MCP patterns, Atrium plugin API (see references.md).
- **Product alignment:** mission's "headless, Action-first, surface parity"; roadmap Phase 1 item 1.

## Standards Applied

- backend/actions, action-transactions, domain-guards: Action shape, transactions, guards ("already suspended").
- backend/http-requests, http-resources, routes: HTTP surface.
- backend/mcp-*: MCP surface.
- backend/config-docs, feature-toggles, publish-tags, container-bindings: config, disabled-by-default surfaces, migration publish tags, `Support\Users` binding.
- backend/slug-references: route-key exception for users.
- database/migrations, models: `roster_profiles`, `Profile` model.
- testing/*: layers, parity, arch, TestCase.

## Decisions made during implementation

- **Relation and helper names are prefixed** (`rosterProfile`, `roster()`, `rosterStatus()`, `isRosterActive()`) rather than `profile()` / `isActive()`, to avoid colliding with methods host user models commonly have already.
- **Status lives on the profile row**, so it works the same in every user mode (the trait-less model included).
- **Self-protection guards take an optional `actor`** (`execute($user, $data, $actor)`). Surfaces pass the authenticated user; plain PHP callers can omit it.
- **No `roster.ui.enabled` toggle.** Atrium's discovery registers the plugin regardless (the same issue Impex's toggle has), so hiding uses Atrium's own `atrium.disabled => ['roster']`.
- **`UpdateUserAction::rules(?Model $user)`** takes the target user so the email unique rule ignores it. All surfaces pass the user.
- **MCP deletes return a text response** ("User deleted."), not structured content, mirroring HTTP's 204.
- **Owned-mode `Models\User` is not final** (hosts may extend it); the arch test exempts it.
- **The empty `roster-assets` publish tag was dropped**: there are no assets yet.
- **The parity arch test checks three surfaces** (HTTP requests, MCP requests, Atrium UI controllers). Verified that it fails when an MCP request is removed.
