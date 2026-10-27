# Roster — Phase 2, Feature 4: CSV Import / Export via Impex

## Context

This is the last Phase 2 roadmap item, "bulk user/member import and export via `refactor-circus/impex`". Impex (`../Impex`) is a durable workflow engine. It provides flows, `batch()` with per-item results and idempotent item keys, `signal()`/`wait()` for human confirmation, a ledger, and run tracking in its own Atrium pages.

It does **not** provide CSV reading or writing, file downloads, or a user-facing progress view (its run page is an operator view that exposes payloads). Roster builds those.

Builds on:
- **Actions:** `CreateUserAction`, `ManagesMemberships::join`, `AddTeamMemberAction`, `AssignRoleAction`, `CreateInvitationAction`, `CreateTeamAction`, `ListUsersAction`, `ListMembersAction`, `ListAuditEntriesAction`.
- **Access:** `Access/Authorizer`.
- **Audit:** `Audit/Surface`, `AuditRecorder`.
- **Optional-dependency pattern from SSO:** `Sso::available()`, `suggest` and `require-dev`.

## Decisions (from shaping)

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

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-csv-import-export/` with `plan.md`, `shape.md`, `standards.md` and `references.md` (Impex docs `02-flows`, `05-signals-timers`, `06-scale`, `12-extending`, `15-testing`, `src/Contracts/BatchSource.php`, `src/Flows/ResumableAction.php`).

## Task 2: Dependencies, storage, config

- **composer:** a vcs repo `https://github.com/Refactor-Circus/Impex.git`, `suggest` and `require-dev` `refactor-circus/impex: dev-main`, then `composer update`.
- **TestCase:** load Impex's migrations from `vendor/refactor-circus/impex/database/migrations`.
- **Migration** `2026_01_01_000008_create_roster_transfer_tables.php`, creating `roster_transfers`:
  - ulid, `type`, `organization_id` nullable FK nullOnDelete, `requested_by` (UserKey)
  - `status` (`validating|awaiting_confirmation|running|completed|failed|cancelled|expired`), `impex_run_id` nullable
  - `input_path`, `output_path`, `filters` json, `report` json (summary counts + rows), `row_count`
  - `confirmed_at`, `finished_at`, `expires_at`, timestamps
- **Model** `Transfer` (final, HasUlids, with a factory) and enums `TransferType`, `TransferStatus`.
- **Config `transfers`:** `disk`, `max_bytes`, `max_rows`, `confirm_within_hours`, `retention_days`, `chunk`. Banner with security notes.

## Task 3: CSV and Impex layer (`src/Transfers/`)

- `Csv/Reader.php`: header-mapped rows from a disk path via `SplFileObject`. Handles a BOM and trims cells. Enforces limits with field-keyed validation errors.
- `Csv/Writer.php`: streamed writes with formula neutralization.
- `Transfers.php`: `available()` (`class_exists(Impex)`), plus flow registration in the provider via `callAfterResolving(FlowRegistry)`.
- `Planners/{MembersPlanner,UsersPlanner,TeamsPlanner}.php`: validate one row against current state, returning `{row, action, reasons, payload}`. Shared by the preview and by each apply (re-checked at apply time).
- `Flows/ImportFlow.php` and `Flows/ExportFlow.php`.
- Flow actions: `Flows/Actions/{ValidateImport,ApplyImportRow,FinishImport,BuildExport,FinishExport}.php`. Arguments are ids only, per Impex rules.
- `Flows/CsvRowsSource.php`: a `BatchSource` over the stored CSV, with a byte-offset cursor and item key = sha256(normalized row).
- `Exporters/{MembersExporter,UsersExporter,AuditExporter}.php`: header plus a chunked query using the same scoping as the list Actions.

## Task 4: Actions (parity on HTTP, MCP, Atrium)

| Action | Notes |
|---|---|
| `StartImportAction` | `type`, `organization?`, the CSV (an uploaded file on HTTP/Atrium; a string `content` on MCP, within `max_bytes`); stores the file, creates a Transfer, starts the run. Owners: requester + org. |
| `ConfirmImportAction` | re-checks permissions; `Impex::signal($run, 'confirm', ['confirmed' => true])` |
| `CancelTransferAction` | while validating/awaiting confirmation: signal declined or cancel the run |
| `StartExportAction` | `type`, `organization?`, `filters` (audit filters) |
| `ListTransfersAction` | own transfers, plus the org's for those with manage/view in the org; filters type/status/organization |
| `ShowTransferAction` | with the report |
| Download | not an Action, a file route: `GET roster/transfers/{transfer}/download` (HTTP, authorized), Atrium's download button, MCP's `show-transfer-tool` returns a signed `download_url` |

Plus:
- Events for each Action.
- `TransfersUnavailableException`.
- `roster:prune-transfers`.

## Task 5: Surfaces

- **HTTP:**
  - `POST roster/imports` (multipart: type, organization, file)
  - `POST roster/imports/{transfer}/confirm`
  - `POST roster/exports`
  - `GET roster/transfers` (index) and `GET roster/transfers/{transfer}` (show)
  - `DELETE roster/transfers/{transfer}` (cancel)
  - `GET roster/transfers/{transfer}/download`
  - `TransferResource` (status, summary, rows paginated in show, `download_url` when ready)
- **MCP:** `start-import-tool` (CSV content), `confirm-import-tool`, `start-export-tool`, `list-transfers-tool`, `show-transfer-tool`, `cancel-transfer-tool`.
- **Atrium:**
  - An "Imports & exports" page: list, a new import form (type, org, file), an export form.
  - A transfer page with the preview table (row, action, reasons), confirm/cancel buttons, a progress bar (from Impex batch counters), a download button, and the per-row results.
  - Import/Export CSV buttons on the organization Members/Teams tabs and the Users page.
- **Surface `import`** during row apply, and `transfer` context in audit entries.

## Task 6: Tests

- **Setup:** an Impex-enabled TestCase with its migrations and `queue.default=sync`. Flows are driven through `Impex::run` synchronously plus the signal.
- **Reader/Writer:** BOM, header mapping, limits, formula neutralization, a `password` column rejected.
- **Planners:** for each type, every action and reason (domain rule → invite, existing member → update, unknown team → error, no role escalation: a role the requester can't assign → error).
- **Import flow end to end:**
  - the preview creates nothing
  - confirm applies
  - per-row errors are reported, not fatal
  - retries don't double-apply (item keys)
  - permissions are re-checked at confirm
  - expiry cancels
  - audit entries carry the requester as actor, surface `import`, and `transfer` context
- **Export flow:** every type, column contents, audit visibility scoping, formula neutralization, download authorization (requester / permitted / forbidden), retention prune.
- **Unavailable mode:** a TestCase simulating no Impex, with the clear exception.
- **Surfaces:** HTTP (multipart), MCP (content string, signed download URL), Atrium pages, the authorization matrix, parity and docs tests, and a browser test (upload → preview → confirm → results).

## Task 7: Docs

README "CSV import and export" section:
- Impex setup: `composer require refactor-circus/impex`, publish and migrate Impex's tables, run a queue worker and the scheduler
- the column formats for each type, the preview/confirm flow, permissions, limits, formula safety, downloads and retention, audit

Plus CHANGELOG, Boost skill, the spec's decisions, the roadmap (Phase 2 complete), and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- Workbench (Impex migrations added to testbench.yaml, queue sync): upload a members CSV for Acme mixing an `acme.test` email, a foreign email and a bad row. The preview shows link/create, invite and error. Confirm: the members and invitation exist, and the bad row is reported. Export members: the download opens with formula cells neutralized. The audit log shows the rows with surface `import` and the requester as actor.
