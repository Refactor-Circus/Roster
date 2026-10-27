# CSV Import / Export — Shaping Notes

## Scope

Phase 2, feature 4 (the last). Bulk CSV import of members, users and teams with a validate → preview → confirm step, and CSV export of members, users and the audit log. Runs on Impex flows, batches and signals.

## Decisions

- **Imports:**
  - **Members into an organization:** `email, name, display_name, teams, role`.
    - On the org's domains: link or create the account and join it.
    - Elsewhere: **send an invitation** (with the teams). Accounts are never claimed outside the org's domains, the same rule as SCIM.
    - Existing members get the listed teams and role added; nothing is ever removed.
  - **Users (global):** `email, name, display_name`. **No passwords**: a `password` column rejects the file. Users get random passwords and reset or use SSO.
  - **Teams in an organization:** `name, slug, members` (emails of existing org members).
- **Exports:**
  - Org members (teams, roles, status, joined)
  - Users (global, with profile and status)
  - Audit log (filters as in `ListAuditEntriesAction`; visibility rules respected)
- **Import is validate → preview → confirm.**
  - Upload validates every row and stores a report: per row `create` / `link` / `invite` / `update` / `skip` / `error` with reasons. **Nothing changes until confirmed.**
  - Confirming applies row by row. Errors are reported per row; there is no all-or-nothing.
  - Unconfirmed imports expire after `roster.transfers.confirm_within_hours` (24).
- **Permissions** reuse the existing ones, so a CSV can never do or reveal more than the UI:

  | Operation | Permission |
  |---|---|
  | members import | `roster.members.manage` in the org; invitation rows also need `roster.invitations.manage`, otherwise those rows fail |
  | teams import | `roster.teams.manage` |
  | users import | `roster.users.create` (global) |
  | exports | `members.view` (org), `users.view` (global), `audit.view` (global or org-scoped) |

  The requester's permissions are checked at start **and** at confirm.
- **Impex is optional** (`suggest` + `require-dev`, like the SSO packages), because it needs a queue worker, its scheduler and published migrations.
  - Without it, the transfer Actions throw `TransfersUnavailableException` naming the `composer require` to run, and no transfer routes/screens break (the Atrium page shows the setup note).
- **Roster keeps its own record**, `roster_transfers`. It holds type, organization, requester, status, the Impex run id, input/output files, the report and the filters, and drives a **user-facing** progress, preview and download page in Atrium, plus API/MCP. Links to Impex's operator run page are shown only to those who can view Atrium's Impex section.
- **Flows** (registered with Impex's `FlowRegistry` as `roster:import`, `roster:export`):
  - **`roster:import`**:
    1. `ValidateImport` (parse and plan, writes the preview)
    2. `signal('confirm')`, waiting up to the window; the default is cancel
    3. `batch(CsvRowsSource)->using(ApplyImportRow)->chunk(200)`, where item keys are row content hashes, so retries never double-apply
    4. `FinishImport` (aggregates batch item results into the report)
  - **`roster:export`**: `BuildExport`, a `ResumableAction` that streams rows to the transfers disk with an id cursor, then `FinishExport`.
- **Files and safety:**
  - Uploads and exports live on `roster.transfers.disk` (default `local`, private) under `roster/transfers/{id}`.
  - Limits: `max_bytes` (5 MB) and `max_rows` (10,000); a header row is required; UTF-8 with a BOM is tolerated.
  - **Exports neutralize spreadsheet formula injection**: cells starting with `= + - @` (or a tab/CR) get a leading `'`.
  - Downloads go through Roster's own route, authorized as the export's requester, or anyone holding the export's view permission in scope. MCP gets a 15-minute signed URL.
  - Files are deleted by `roster:prune-transfers` after `retention_days` (7).
- **Audit:**
  - Rows are applied **as the requester** (the worker temporarily sets the auth user), with surface `import` and context `transfer` {id, type}.
  - The transfer lifecycle (`transfer.started`, `transfer.confirmed`, `transfer.cancelled`, `transfer.finished`) is recorded through Action events.


## Decisions made during implementation

- **Rows are applied as the confirmer**, not the uploader. `ConfirmImportAction` re-checks the confirmer's permission and records them as `requested_by`, because the confirmer is who actually makes the changes.
- **Every row is planned again at apply time.** Batch item keys are `line-N` (unique per batch in Impex), and re-planning turns already-applied rows into `skip`. A redelivery test confirms that rows are never applied twice. This replaced the planned content-hash keys, which added nothing.
- **Imported members still emit `MemberAdding`/`MemberAdded`.** `AddMemberAction` would record the source as `direct`, so the planner joins with source `import` and dispatches the same events. That keeps `member.added` in the audit log.
- **The signal deadline comes from `ValidateImport`'s recorded result**, not from `now()` inside the flow, so replays stay deterministic.
- **Signed MCP download links** use a route in `routes/web.php` (`roster.transfers.file`, `signed` + `throttle:roster`). It is always on, like the invitation page, and the signature (15 minutes) is the authorization. The authorized API route is `roster.transfers.download`.
- **Listing:**
  - Everyone may list their own transfers.
  - An organization's transfers need `roster.members.view` there.
  - Everyone's transfers need `roster.users.view`.
  - Show and download are allowed to the requester or to holders of the type's permission in scope. Confirm always needs the permission.
- **Authorization before validation:** with an unknown `type`, the check falls back to a global permission (`users.create` / `users.view`), so unauthorized callers get 403 rather than a 422 that would leak the rules.
- **The Atrium nav item** shows to holders of any transfer permission globally. Organization admins reach their organization's page via the "Import / export CSV" button on the organization (and Users) pages.
- **Show returns the full report.** It is bounded by `max_rows`. List responses omit rows.
- **Progress** comes from Impex's `impex_batches` counters.
- **A `RunFailed` listener** marks the transfer failed.
- **The workbench** loads Impex's migrations and sets `QUEUE_CONNECTION=sync`, so the demo needs no worker.

## Context

- **Visuals:** None.
- **References:** see references.md.
- **Product alignment:** roadmap Phase 2 item 4. This completes the roadmap.
- **Exploration finding:** Impex provides flows, batches, signals and run tracking, but no CSV, no file downloads and no user-facing progress. Roster builds those.

## Standards Applied

- Same set as SCIM. `runtime-exceptions` covers `TransfersUnavailableException`. Flow actions take ids only (Impex rule).
