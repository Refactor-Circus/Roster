# Roster — Phase 2, Feature 2: Impersonation

## Context

Phase 2 continues after the audit log (spec `2026-10-01-1218-audit-log`). Admins need to see the app as a specific user, with every action logged and tight limits. This builds on:
- users/status: `Support/Users`, `UserStatus`
- permissions and the escalation idea: `Access/Permissions`, `Actions/Concerns/GuardsEscalation.php`
- the audit recorder: `Audit/AuditRecorder.php`, `Audit/Surface.php`
- the web-page pattern from invitations: `Http/Web/InvitationWebController.php`, `routes/web.php`, signed routes

## Decisions (from shaping)

- **Who may impersonate:** the new permission `roster.users.impersonate`. Global holders may impersonate anyone; holders in an organization may impersonate that organization's members (start with `organization`). It's never granted implicitly (not in `admin`; owners' implicit grant excludes `roster.users.*`).
  - Refused:
    - impersonating yourself;
    - an inactive user;
    - a super-admin, unless you are one;
    - anyone holding a permission you lack in that scope (the escalation guard);
    - starting while already impersonating.
- **Mechanism: a session plus a signed one-time link.**
  - `StartImpersonationAction` (target, reason, optional organization, actor) creates a `roster_impersonations` record and returns a short-lived one-time link: `URL::temporarySignedRoute('roster.impersonation.enter', +5 min, token)`.
  - Only the token's hash is stored.
  - **Opening the link requires the browser to be signed in as the impersonator**, so a leaked link is useless to anyone else. It's consumed once.
  - Entering swaps the web guard's user to the target and remembers the impersonation in the session.
  - The API and MCP return the link (shown once). Atrium redirects straight to it.
- **Stopping:**
  - `StopImpersonationAction` ends a record. The impersonator can end their own; a global `roster.users.impersonate` holder can force-end anyone's.
  - A "Return to my account" POST (`roster.impersonation.leave`) restores the impersonator and redirects to `roster.impersonation.return_to`, by default the target's Atrium page.
  - A force-ended or expired impersonation is detected on the next request and the impersonator is restored.
- **Safety rails:**
  - **A reason is required** (stored, audited).
  - **A time limit:** `roster.impersonation.ttl_minutes` (default 30). The middleware auto-ends the session after it.
  - **A banner:** a `<x-roster::impersonation-banner />` component for host layouts, which shows who you are, who you really are, the time left and a stop button. Roster's own Atrium pages include it.
  - **Blocked while impersonating:**
    - permissions listed in `roster.impersonation.blocked` (default: `roster.users.impersonate`, `roster.users.delete`, `roster.roles.manage`, `roster.roles.assign`, `roster.permissions.*`-style wildcard support) are refused by Roster's Authorizer **and** by `Gate::before` (explicit `false`), so host abilities can be blocked too;
    - **changing the impersonated account's email or password** (a guard in `UpdateUserAction`).
- **Audit:** actions taken while impersonating record actor = the impersonated user, plus `context.impersonator` {id, label}. `impersonation.started` and `impersonation.stopped` entries come from the Action events (with reason, target and whether it expired or was forced).
- **Middleware:** `roster.impersonation` keeps the session in sync (expiry, force-end) and shares state with views. It's pushed onto the `web` group automatically (`roster.impersonation.middleware_group`, default `web`; null to add it yourself).

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-impersonation/` with `plan.md`, `shape.md`, `standards.md` and `references.md`.

## Task 2: Storage and config

- **Migration** `2026_01_01_000005_create_roster_impersonation_tables.php`, creating `roster_impersonations`:
  - ulid id, `impersonator_id` / `user_id` (UserKey)
  - `organization_id` nullable FK nullOnDelete, `reason`
  - `token_hash` unique nullable (cleared once used)
  - `link_expires_at`, `started_at`, `expires_at`, `ended_at`, `end_reason` (`stopped|expired|forced`)
  - timestamps, index(`impersonator_id`), index(`user_id`)
- **`Models/Impersonation`:** final, HasUlids, `impersonator()` / `user()` / `organization()`, `isActive()`, scope `active()`. With a factory.
- **Config `impersonation`:** `ttl_minutes` 30, `link_minutes` 5, `blocked` [...], `redirect` '/', `return_to` null (Atrium user page when null), `middleware_group` 'web'. The banner covers its security notes.
- **`BuiltInRoles`:** add `roster.users.impersonate`.

## Task 3: Core

- **`src/Impersonation/ImpersonationContext.php`** (scoped): reads the session (`roster.impersonation` → record id), exposing `active(): ?Impersonation`, `impersonator(): ?Model`, `isBlocked(string $permission): bool` (with wildcard matching).
- **`src/Impersonation/Impersonator.php`:** `enter(Impersonation, Request)` swaps the web guard user and writes the session; `leave(Request, string $why)` restores the impersonator, ends the record and clears the session.
- **Middleware** `src/Http/Middleware/SyncImpersonation.php` (alias `roster.impersonation`): expires or force-ends, then restores and flashes a message; shares `rosterImpersonation` with views.
- **Authorizer:** when impersonating and the permission is blocked, return false. **Gate::before:** a blocked ability returns false *before* the super-admin shortcut.
- **`UpdateUserAction`:** when impersonating and the target is the impersonated user, a field-keyed 422 on `email` / `password`.
- **AuditRecorder and RecordAuditEventAction:** add `impersonator` to context when active.

## Task 4: Actions

| Action | Notes |
|---|---|
| `StartImpersonationAction` | rules: `reason` required (max 500), `organization` optional. Guards: self, inactive, super, escalation (`Permissions::for(target, scope)` ⊆ `for(actor, scope)`), org membership when scoped, already impersonating. Returns `[Impersonation, url]` via a small DTO `StartedImpersonation`. |
| `EnterImpersonationAction` | token + actor: valid/unused/unexpired, actor is the impersonator, target still active; sets `started_at` and `expires_at`, clears the token. |
| `StopImpersonationAction` | record + actor + why; idempotent on ended records. |
| `ListImpersonationsAction` | filters `active`, `user`, `impersonator`, `organization`; newest first. |

## Task 5: Surfaces

- **HTTP:**
  - `POST roster/users/{user}/impersonate` → 201 `{data: impersonation, url}`
  - `GET roster/impersonations`
  - `DELETE roster/impersonations/{impersonation}` (stop/force)
  - Abilities:
    - start: `roster.users.impersonate` in the scope from input;
    - list: the same, global or org;
    - stop: own (self via impersonator) or `roster.users.impersonate`.
  - `ImpersonationResource`: no token.
- **Web** (`routes/web.php`, `Http/Web/ImpersonationWebController`):
  - `GET roster/impersonate/{token}` (signed, `auth`) → enter, then redirect to `roster.impersonation.redirect`
  - `POST roster/impersonate/leave` → leave
- **MCP:** `start-impersonation-tool` (returns the link), `list-impersonations-tool`, `stop-impersonation-tool`. Enter and leave are browser-only: they are **web** UI surface Actions. `EnterImpersonationAction` is reached from the Web controller (UI) and the HTTP/MCP surfaces through a documented **parity exception**, because entering swaps a browser session and has no API meaning. The parity test gains an exceptions map with reasons (first use).
- **Atrium:**
  - An "Impersonate" form (reason, organization) on the user page, shown only to authorized users, that redirects to the link.
  - An "Impersonations" page (active and history, end button).
  - The banner partial in Roster's Atrium pages.
- **Blade component** `resources/views/components/impersonation-banner.blade.php`, registered as `x-roster::impersonation-banner`.

## Task 6: Tests

- `tests/Feature/ImpersonationActionsTest.php`: every start guard; enter (wrong browser user, reused or expired link, target became inactive); stop (own, forced, idempotent); listing.
- `tests/Feature/Web/ImpersonationFlowTest.php`:
  - the full flow: start → link → enter (the auth user becomes the target) → banner visible → leave (restored)
  - TTL expiry restores on the next request
  - a force-end restores on the next request
  - the session survives a normal request
- `tests/Feature/ImpersonationSafetyTest.php`: blocked permissions denied through the Authorizer and the Gate (a host ability via config wildcard); email/password change blocked; audit entries carry the impersonator, plus started/stopped entries with the reason.
- **Authorization matrix:** new routes and tools. Parity exception covered.
- HTTP/MCP surface tests (the link returned once, no token in the list).
- Atrium page tests and a browser test (impersonate from the user page, see the banner, return).

## Task 7: Docs

README (an Impersonation section with security notes, the banner component, config), CHANGELOG, Boost skill, the spec's implementation decisions, the roadmap tick, and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- Workbench: as admin, impersonate Grace from Atrium with a reason. You're now Grace with the banner showing. Try role management (blocked), then return. The audit log shows started, the actions as Grace with the impersonator, and stopped.
