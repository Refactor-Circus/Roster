# Organization Sync — Shaping Notes

## Scope

Let apps import and sync organizations from external systems of record (ERP, CRM, …) and keep track of each organization's external record ID and account number.

## Decisions

- **External IDs per source.** A link table (`roster_organization_links`) holds `source`, `external_id`, `account_number` and `synced_at`, with `unique(source, external_id)` and `unique(organization_id, source)`. One organization can be linked to several systems.
- **The owner becomes optional.**
  - Synced organizations may have no owner.
  - A sync record's owner is applied only on create, or when the organization has none.
  - Changing an existing owner stays with `TransferOwnershipAction`.
  - Personal organizations still need an owner.
- **No locking.** Synced fields stay editable locally; the next sync wins.
- **Sync paths:** an upsert Action, a bulk Action, a CSV import type (`import_organizations`), lookup filters, and Atrium display and editing of links.
- **Defaults I chose (accepted with the plan):**
  - a new global `roster.organizations.sync` permission
  - bulk sync applies each record on its own, capped at `roster.organizations.sync_batch` (500)
  - sync updates only the fields present
  - `organization` (a slug) links an existing organization on its first sync
  - the sync Actions are an Atrium parity exception (CSV import and links cover the UI)
  - `owner_id` is made nullable in the original migration, since the package is unreleased

## Decisions made during implementation

- **Several sources per organization meant a link table**, not columns on `roster_organizations`. `OrganizationResource` always includes `links`, and every organization Action eager-loads them.
- **The sync Action validates inside `execute()`**, against the organization the record resolves to: create rules for new ones, update rules (domain and slug uniqueness ignoring itself) for existing ones. That's because bulk records and CSV rows don't pass through a FormRequest. `rules()` only describes the record's shape.
- **No-op detection:** fields are compared before calling `UpdateOrganizationAction` (domains compared as sorted, lower-cased lists), so an unchanged sync writes no `organization.updated` entry and returns `unchanged`. A new link or a changed account number counts as `updated`.
- **Claiming ownership** sets `owner_id` and joins the owner as a member directly, without `TransferOwnershipAction`, which requires an existing membership. This only happens for ownerless organizations.
- **`SyncOrganizationAction::preview()`** is a side-effect-free dry run (no writes, no events) used by the CSV preview, so preview and apply share one decision path. It's the only non-`execute` public method on an Action, and that's deliberate.
- **CSV:** `name` is optional in the header (required only for new organizations), so a file can update account numbers alone. Blank cells leave fields untouched. `owner` is an email, mapped to the user key.
- **HTTP:**
  - A single sync is an idempotent `PUT /organizations/external/{source}/{externalId}`, returning 201 when created and 200 otherwise. `externalId` may contain slashes.
  - Links use `PUT`/`DELETE /organizations/{organization}/links/{source}`.
  - The Atrium link form posts `source` in the body.
- **An integration user with only `roster.organizations.sync` can't list organizations.** Lookups need `roster.organizations.view`; the README says so.
- **Export (follow-up request):**
  - `export_organizations` (needs `roster.organizations.view`) writes exactly the import columns, so a file round-trips; a test proves a re-import is all `skip`.
  - It writes one row per link; unlinked organizations get a blank-source row.
  - The filter key is `filters.external_source`, because `filters.source` already means roster/app for the audit export. `StartExportAction` keeps each type's own filter keys.
  - The Atrium export form shows only the fields the picked type uses (Alpine), and the Organizations index has an "Import / export CSV" button.
- **Import templates (follow-up request, "across the board"):**
  - One CSV per import type in `resources/import-templates/`: the header plus `#` comment rows. The Reader now skips rows whose first cell starts with `#`, and a file with no rows is refused.
  - Published copies (`roster-import-templates` → `resources/roster/import-templates`) win.
  - `ShowImportTemplateAction` covers HTTP, MCP and Atrium links. It's open to any signed-in user because templates hold no data; the authorization tests list this in `OPEN_TO_SIGNED_IN`.
  - It works without Impex, and isn't audited (it's a read).
  - A test keeps each template's header equal to its `TransferType::columns()`.
- **Audit:** the bulk Action records `organizations.synced` with its `summary` counts; each record also records its own `organization.synced`.

## Context

- **Visuals:** None.
- **References:** see references.md.
- **Product alignment:** a post-roadmap request from the user ("consumers may import/sync organizations from external systems… keep track of the external id… external record id and account number").

## Standards Applied

The same set as the CSV feature: Action shape, transactions, field-keyed validation errors, the three-surface parity rule, and tests through public APIs.
