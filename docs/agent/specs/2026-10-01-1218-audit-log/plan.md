# Roster — Phase 2, Feature 1: Audit Log

## Context

Phase 1 (users, orgs/teams, roles/permissions, surfaces) is complete. Specs are in `agent-os/specs/2026-10-01-*`. The first Phase 2 roadmap item is the audit log: record who changed users, roles and memberships, and offer an activity feed.

Every Roster Action already dispatches a `*ingActionEvent` (implements `RefactorCircus\Roster\Contracts\ActionStartingEvent`) and a `*edActionEvent` (`ActionFinishedEvent`) around its work (`src/Events/Action/*`). The audit log hooks those events, so **no Action changes**. It adds read surfaces on HTTP, MCP and Atrium with the existing authorization model (`src/Access/Authorizer.php`, `Permissions`).

## Decisions (from shaping)

- **Coverage: every mutation.** All Roster Actions that change data. Reads (`*Listed`, `*Shown`, `UserPermissionsListed`, `RoleAssignmentsListed` …) are skipped.
- **Detail per entry:**
  - actor (user or null for "system"), `action` (e.g. `user.suspended`), subject (morph type, key and a human label), organization scope
  - surface: `http` | `mcp` | `atrium` | `web` | `cli` | `code`
  - IP and user agent
  - `changes`: `{field: [old, new]}` from before/after snapshots
- **Snapshots** are taken on the Starting event and diffed on the Finished one:
  - a user includes `profile.*` fields (status lives there);
  - a role includes its `permissions` list;
  - an organization includes its `domains`.
  - A create has no "before"; a delete has no "after".
- **Secrets are always redacted:** passwords, remember tokens, `token_hash`, the mapped password column, plus `roster.audit.redact`.
- **Visibility:** a new built-in permission, `roster.audit.view`, added to the `admin` org role.
  - Global holders see everything.
  - With an `organization` filter, the check is in that organization's scope, so org admins see their org's entries.
  - With a `user` filter equal to yourself, it's always allowed, so users see entries about or by themselves.
- **Append-only:**
  - No update or delete Actions or surfaces.
  - The `AuditEntry` model refuses to update or delete through Eloquent.
  - Only `roster:prune-audit` removes rows (`roster.audit.retention_days`, default 365; null keeps them forever; schedulable).
- **Hash chain:**
  - Each entry stores `previous_hash` and `hash = sha256(previous_hash . canonical-json(entry))`, written inside a transaction that locks the latest row.
  - `roster:verify-audit` walks the chain and reports the first broken link.
  - After pruning, verification anchors on the oldest remaining entry's `previous_hash` (documented).
- **Ids: auto-increment, a deliberate deviation from the ULID standard.** The chain needs a strict, gap-free order.
- **Toggle:** `roster.audit.enabled` (default true).
- **Host app events go in the same log.**
  - From code:

    ```php
    Roster::audit('invoice.paid')->on($invoice)->in($organization)->by($user)->with(['amount' => 100])->changes(['status' => ['open', 'paid']])->record();
    ```

    (the facade `RefactorCircus\Roster\Facades\Roster` → the `Roster::audit()` builder `Audit\PendingAuditEntry`), or `RecordAuditEventAction`.
  - The same redaction, surface/IP capture and hash chain apply.
  - Each entry carries `source`: `roster` (recorded from Roster's Action events) or `app` (recorded through the API).
    - API-recorded entries are **always** `app`, so callers can't forge Roster's own entries.
    - Feeds and filters show the source.
  - Action names are dot-separated lower case (`invoice.paid`). The subject can be any model (morph type/key/label) or a free `subject_type`/`subject_id`/`subject_label` triple for surfaces.
  - Recording over the API, MCP or Atrium needs the new `roster.audit.record` permission (in the organization's scope when `organization` is given). It is added to the `admin` org role.
  - `RecordAuditEventAction` is a normal Action, so parity applies:
    - `POST roster/audit` (`roster.audit.store`)
    - `record-audit-event-tool`
    - an "Add entry" form on the Atrium audit page (an admin note for something that happened outside the app)

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-audit-log/` with `plan.md`, `shape.md`, `standards.md` (feature 4's set) and `references.md` (`src/Events/Action/*`, the contracts, Access, and the feature 3/4 surfaces).

## Task 2: Storage

- **Migration** `2026_01_01_000004_create_roster_audit_tables.php`, creating `roster_audit_entries`:
  - `id` bigIncrements
  - `source` (`roster` | `app`), `action`, `actor_id` (UserKey, nullable)
  - `subject_type` and `subject_id` (string), `subject_label`
  - `organization_id` (nullable ULID, **no FK**, so history survives deletes)
  - `surface`, `ip`, `user_agent`
  - `changes` json, `context` json
  - `previous_hash`, `hash`, `created_at`
  - Indexes on (`organization_id`, `id`), (`subject_type`, `subject_id`), (`actor_id`) and `action`.
- **`src/Models/AuditEntry.php`:** final, `$timestamps` created_at only. Its `updating` and `deleting` listeners throw `AuditLogIsAppendOnlyException`, a runtime exception. Includes an `actor()` relation and `scopeAbout(Model $user)` (subject or actor).
- **Config `audit`:** `enabled`, `retention_days`, `redact` [], with a banner.

## Task 3: Recording

- **`src/Audit/AuditRecorder.php`** (scoped binding), listening to both contracts:
  - On start: snapshot each Model property of the event (`Snapshots::of($model)`), keyed by class and key.
  - On finish (skipping read events): derive the action name from the event class (`UserSuspendedActionEvent` → `user.suspended`), pick the subject (the first Model property), the organization (an Organization property, or the subject's `organization_id`, or its team's organization), the diff from the snapshot vs `Snapshots::of(fresh)`, and context (reason, role, team slugs).
  - Then write through `AuditLog::append()`.
- **`src/Audit/Snapshots.php`:** per-model snapshot rules (user + profile, role + permissions, organization + domains, assignment with role/org/team) and redaction.
- **`src/Audit/Surface.php`:** the current surface.
  - Set explicitly by `Mcp\Request::persist()` (`mcp`).
  - Otherwise inferred: an `atrium.` route means `atrium`; `roster.invitations.page` / `show` means `web`; a `roster.` route means `http`; console means `cli`; anything else is `code`.
- **`src/Audit/AuditLog.php`:**
  - `append(array $attributes)`: transaction, `lockForUpdate` on the latest row, chain the hash.
  - `verify(): ?int`: the first broken id, or null.
  - `prune(int $days)`.
- The provider registers the listeners only when `roster.audit.enabled === true`.

## Task 4: Actions, permission, commands

- **Actions:**
  - `ListAuditEntriesAction`: filters `organization` (slug), `user` (route key: subject or actor), `subject_type` (`user|organization|team|role|permission|invitation|assignment`), `action`, `since`, `until`, `per_page`; newest first.
  - `ShowAuditEntryAction`.
  - `RecordAuditEventAction`: `action`, a subject (model or triple), `organization` (slug), `changes`, `context`; the actor passed in; source `app`. The `source` filter is added to `ListAuditEntriesAction`.
- **`src/Audit/PendingAuditEntry.php`:** a fluent builder (`on`, `in`, `by`, `with`, `changes`, `record`) that calls `RecordAuditEventAction`. `Roster::audit()` returns it, defaulting the actor to the authenticated user.
- **`BuiltInRoles`:** add `roster.audit.view` and `roster.audit.record` and grant both to `admin`. Sync adds the permission; README notes re-granting on existing installs.
- **Commands:**
  - `roster:prune-audit {--days=}`, with a config default.
  - `roster:verify-audit`, which exits non-zero on a broken chain.

## Task 5: Surfaces

- **HTTP:**
  - `GET roster/audit` (`roster.audit.index`) and `GET roster/audit/{entry}` (`roster.audit.show`).
  - `AuditEntryResource`: id, action, actor summary, subject {type, id, label}, organization slug (looked up, null if deleted), surface, changes, context, created_at. IP and user agent are shown only to holders of global `roster.audit.view`.
  - Requests: `ability()` `roster.audit.view`, `scope()` from the `organization` input, `self()` from the `user` input. The show request checks against the entry's organization.
- **MCP:** `list-audit-entries-tool` and `show-audit-entry-tool`, with the same scope and self rules.
- **Atrium:**
  - An "Audit log" nav item (authorized) with filters and a table.
  - An entry page with a field diff table and the chain hash.
  - An "Activity" card on the user page and an "Activity" tab on the organization page (`ListAuditEntriesAction` filtered).
  - Code: `Http/Ui/AuditUiController`.
- **README:** the routes, tools and an Audit section. The docs test, parity and authorization matrix get the new routes and tools.

## Task 6: Tests

- `tests/Feature/AuditRecordingTest.php`:
  - one representative mutation per area (user create/update/suspend/delete, profile update, org create/update with domains, member add/remove, team seat, invitation create/accept, role create/update permissions, assign/revoke)
  - reads not logged
  - the diff shapes for create, update and delete
  - secrets redacted (password, token)
  - surface detection (http, mcp, atrium, cli/code)
  - actor captured, `enabled=false` records nothing
- `tests/Feature/AuditHostEventsTest.php`:
  - the fluent API and the Action record `app` entries with a model or triple subject
  - redaction applies
  - action names are validated
  - a host entry chains with Roster entries
  - a `source` filter
  - the API can't record `source=roster`
- `tests/Feature/AuditChainTest.php`: hashes chain, `verify` catches a tampered row (raw DB update), appending stays consistent, the model refuses update/delete, prune plus verify anchoring, both commands.
- `tests/Authorization/AuditAuthorizationTest.php`: a global viewer sees all, an org admin sees only their org, a user sees entries about themselves, others get 403. Add the routes and tools to the existing matrices.
- HTTP, MCP (parity) and UI tests for the list/show surfaces.
- One browser test: the audit page lists an entry after a suspension.

## Task 7: Docs

README (Audit section: what's recorded, redaction, visibility, retention, hash chain and verify, scheduling the prune), CHANGELOG, Boost skill, the spec's implementation decisions, and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- Workbench: rebuild. Suspend a user and edit a role in Atrium, then open the Audit log and see both entries with diffs. Run `php vendor/bin/testbench roster:verify-audit` (OK). Tamper with a row via tinker, rerun it, and see it report the broken id.
