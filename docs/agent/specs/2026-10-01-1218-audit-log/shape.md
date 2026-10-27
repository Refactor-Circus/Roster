# Audit Log — Shaping Notes

## Scope

Phase 2, feature 1. An append-only, hash-chained audit log of every Roster mutation, with before/after diffs. Readable globally, per organization, or about yourself. Host apps can record their own events in it too.

## Decisions

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


## Context

- **Visuals:** None.
- **References:** the Action event pairs in `src/Events/Action/*` (the recording hook), Access (visibility), feature 3/4 surfaces.
- **Product alignment:** roadmap Phase 2 item 1.
- **Changed mid-shaping:** the user added the host-event API ("include api for host logs too").

## Standards Applied

- Same set as feature 4. Deviation: auto-increment ids on `roster_audit_entries` (the hash chain needs strict order). `runtime-exceptions` covers `AuditLogIsAppendOnlyException`.

## Decisions made during implementation

- **Entries are about the user when one is involved.** For membership, seat and role events the subject is the user, and the organization, team and role go in `context`. "Activity about a user" is then a simple subject/actor query.
- **Field changes only for snapshotted models.** A create diffs from nothing and a delete to nothing; anything else diffs against the starting snapshot. Models an Action didn't snapshot (e.g. the user in a role revoke) record no field changes.
- **Redacted fields are tracked by hash in snapshots**, so a password change still shows as `password: [redacted] → [redacted]` without the value ever being stored.
- **The actor comes from the default auth guard**, not the request, so queued or console code that logged a user in is attributed correctly.
- **Bug caught by tests:** `RecordAuditEventAction`'s own finish event was also being recorded, doubling every app entry. The recorder now skips `AuditEvent*` events.
- **Surface `cli` vs `code`:** without a Roster or Atrium route, console processes (including tests and queue workers) record `cli`, and IP/user agent are omitted outside http/mcp/atrium/web.
- **Hash payload uses the Unix timestamp and canonical (key-sorted) JSON** of changes/context, so values read back from the database hash identically.
- **The Cortex/MCP surface is `mcp`:** `Mcp\Request::persist()` wraps the call in `Surface::using('mcp')`.
- **The authorization fixtures use an existing audit entry**, and the matrix now covers the 3 audit routes and tools. The docs test enforced the README additions.
