# Impersonation — Shaping Notes

## Scope

Phase 2, feature 2. Admins temporarily act as another user through a browser session, started by a signed one-time link. Every step is audited, there's a time limit and a banner, and sensitive actions are blocked.

## Decisions

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


## Context

- **Visuals:** None.
- **References:** see references.md.
- **Product alignment:** roadmap Phase 2 item 2.
- **Added without asking (security):** the one-time link only works in a browser already signed in as the impersonator.

## Standards Applied

- Same set as the audit log. The first **parity exception** (`EnterImpersonationAction`: browser-only) is recorded with its reason in the parity test.

## Decisions made during implementation

- **Impersonation entries are about the impersonated user.** Context carries the impersonation id, reason, who started it and how it ended. Besides started and stopped, an `impersonation.entered` entry marks when the link was actually used.
- **The middleware reloads the context on every request.** Long-running processes (Octane, queues, tests) reuse the container, and a cached context missed expiry and force-ends. Found by the flow tests.
- **The middleware is appended through the HTTP kernel** (`appendMiddlewareToGroup`), not just the router. The kernel re-syncs its groups onto the router on each request, which silently dropped the router-only push. Found by the flow tests.
- **The blocked check runs before the super-admin shortcut** in `Gate::before`, so impersonating a super-admin never unlocks blocked abilities.
- **Default blocked list:** `roster.users.impersonate`, `roster.users.delete`, `roster.roles.manage`, `roster.roles.assign`, `roster.audit.record` (no forging app audit entries as someone else).
- **The first parity exception:** `EnterImpersonationAction` is UI-only, with the reason recorded in `tests/Pest.php` `PARITY_EXCEPTIONS`. Leaving goes through `StopImpersonationAction`, which every surface has.
- **The banner is an anonymous component** (`Blade::anonymousComponentPath(..., 'roster')`). Roster's Atrium pages get it through the shared status partial.
- **A flaky Phase 1 test was fixed while here:** Faker's `name()` can produce "Prof. …", which matched a search assertion.
