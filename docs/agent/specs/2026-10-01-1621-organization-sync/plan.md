# Roster — Organization sync from external systems

## Context

Apps using Roster often keep their customer organizations in another system of record, such as an ERP or a CRM. They need to:
- create and update Roster organizations from those records, in scheduled bulk jobs or one at a time;
- remember each organization's ID in the source system and its account number;
- find an organization again by either value.

Today an organization has no external identity. Every organization must also have an owner user, which ERP records don't provide.

## Decisions (from shaping)

- **External IDs per source.** A new link table holds one row per organization per source system:
  - columns: `source` (e.g. `erp`), `external_id`, `account_number`, `synced_at`
  - `unique(source, external_id)` and `unique(organization_id, source)`
  - One organization can be linked to an ERP and a CRM at the same time.
- **The owner becomes optional.** Synced organizations may have no owner until someone transfers ownership to a member.
  - A sync record may name an owner. It is applied on create, or when the organization has no owner yet.
  - Sync never moves ownership away from an existing owner; `TransferOwnershipAction` stays the only way to change it.
  - Personal organizations still require an owner.
- **No locking.** Synced fields stay editable in Roster, and the next sync overwrites them.
- **Sync paths:**
  - an upsert Action (single record) and a bulk Action (batch)
  - a CSV import type
  - lookup filters by source, external ID and account number
  - Atrium display and editing of links

## Decisions I made (sensible defaults, open to change)

- **New permission `roster.organizations.sync` (global)** for both sync Actions and the CSV import type.
  - An integration user only needs this one permission.
  - It is added to `BuiltInRoles` and to `roster:sync-permissions`.
  - Link and unlink need `roster.organizations.update` in the organization.
- **Bulk sync is not all-or-nothing.** Each record is validated and applied in its own transaction, and the response lists `created` / `updated` / `unchanged` / `error` per record. The batch size is capped by `roster.organizations.sync_batch` (500).
- **Sync updates only the fields present in the record.** Omitted fields are left alone; an empty `domains` array clears the domains.
- **To link an existing organization on its first sync**, the record may pass `organization` (a slug). Without it, an unknown `(source, external_id)` creates a new organization.
- **Atrium parity exception:** the two sync Actions are machine-to-machine. In Atrium, the same results come from the CSV import (bulk) and the link form (single). This is documented in `PARITY_EXCEPTIONS`.
- **The owner column becomes nullable** by editing the original organizations migration in place. The package is unreleased (no tags), so a separate alter migration isn't needed. This is recorded in the spec.

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-organization-sync/` with `plan.md`, `shape.md` (the decisions above, plus a "Decisions made during implementation" section), `standards.md` and `references.md`.

Add the feature to `agent-os/product/roadmap.md` as a post-roadmap item.

## Task 2: Schema and model

- **Organizations migration:** make `owner_id` `->nullable()`.
- **New migration** `2026_01_01_000009_create_roster_organization_links_table.php`:
  - ulid `id`, `organization_id` FK `cascadeOnDelete`
  - `source` string(64), `external_id` string(191), `account_number` nullable string(191) (indexed), `synced_at` nullable
  - timestamps, plus the two unique constraints
- **Model** `src/Models/OrganizationLink.php` (final, HasUlids, factory). Add `Organization::links()` (hasMany), and make `Organization::isOwnedBy()` null-safe (`owner_id === null` → false).
- **Config:** add `roster.organizations.sync_batch` (500), with a doc banner.

## Task 3: Owner optional

- **`CreateOrganizationAction`:** `owner` becomes `sometimes|nullable|exists`. With no owner, it skips the owner join; a personal organization without an owner throws a `ValidationException`.
- **Call sites to check:**
  - `Access/Permissions.php:113`, `RemoveMemberAction`, `ScimUsers:217`, `ManagesMemberships::join`, `TransferOwnershipAction` (it must work from no owner)
  - `DeleteUserAction` needs no change
  - `OrganizationResource` already returns null
- **Surfaces:**
  - `CreateOrganizationTool`: `owner` is no longer required.
  - Atrium create form: the owner hint says it's optional.
  - Organization page: show a "No owner" note; the existing "Make owner" button covers assigning one.

## Task 4: Actions (and events, generated as before)

| Action | Notes |
|---|---|
| `SyncOrganizationAction` | Rules: `source` (required, lowercase alpha_dash, max 64), `external_id` (required, max 191), `account_number`, `name` (required when creating), `slug`, `domains`, `auto_join`, `owner`, `organization` (slug, to link an existing one on first sync). Finds the link → `UpdateOrganizationAction` with the present fields; otherwise creates via `CreateOrganizationAction` and adds the link. Sets `account_number` and `synced_at`. Returns `OrganizationSyncResult {organization, outcome}`. |
| `SyncOrganizationsAction` | `records` array (max `sync_batch`). Runs `SyncOrganizationAction` per record, catching `ValidationException` per record. Returns a list of results with errors. |
| `LinkOrganizationAction` | `(Organization, {source, external_id, account_number})`. Adds or updates that organization's link for the source. Refuses an ID that is already linked to a different organization. |
| `UnlinkOrganizationAction` | `(Organization, source)` |
| `ListOrganizationsAction` | New filters `source`, `external_id`, `account_number` (exact match). |

Audit entries come from the Action events as usual (`organization.synced`, `organization.linked`, `organization.unlinked`). A sync running inside a CSV import records surface `import`.

## Task 5: CSV import type

- **New `TransferType::ImportOrganizations`:**
  - columns: required `source`, `external_id`, `name`; optional `account_number`, `slug`, `domains`, `owner` (an email)
  - permission `roster.organizations.sync`; no organization needed
- **`Transfers/Planners/OrganizationsPlanner.php`:**
  - plan: create / update / skip (no changes) / error, covering unknown owner email, domain taken, slug taken
  - apply: maps the owner email to a user, then calls `SyncOrganizationAction`
- Add it to `Transfers::planner()`, the Atrium import type list, the README column table and the lang strings.

## Task 6: Surfaces (HTTP / MCP / Atrium)

- **HTTP:**
  - `PUT roster/organizations/external/{source}/{externalId}` (single upsert; 201 when created, 200 when updated)
  - `POST roster/organizations/sync` (bulk, `{records: [...]}` → per-record results)
  - `PUT roster/organizations/{organization}/links/{source}` and `DELETE roster/organizations/{organization}/links/{source}`
  - `OrganizationResource` gains `links: [{source, external_id, account_number, synced_at}]`
- **MCP:** `sync-organization-tool`, `sync-organizations-tool`, `link-organization-tool` and `unlink-organization-tool`. `list-organizations-tool` gains the new filters. Add a line to the server instructions.
- **Atrium:**
  - Organizations list: an "External" column (`source: external_id`) and source / external ID / account number filters.
  - Organization Settings tab: an "External links" card listing the links (with synced time), an add/edit form (source, external ID, account number) and Unlink.
  - "Import organizations" in the Imports & exports type list.
  - Use only classes in Atrium's CSS or `partials/styles` (StylesTest enforces this).

## Task 7: Tests

- **Sync:** create, then update the same record, and a no-change sync (`unchanged`). Also: present-fields-only, linking an existing organization by slug, the per-source uniqueness conflict, the same organization linked to two sources, owner applied only when there is none, an ownerless create, and a personal organization requiring an owner.
- **Bulk:** mixed results, per-record errors, and the batch cap.
- **Owner optional:** an ownerless organization is excluded from owner-based permissions, can't remove a non-existent owner, and transfer ownership works from no owner.
- **CSV:** `import_organizations` preview and confirm, plus an unknown owner email error.
- **Surfaces:**
  - HTTP and MCP parity; list filters
  - Atrium link/unlink and the list filters
  - the authorization matrix (fixtures, tool args, screens), the docs test, the parity exceptions and the styles test
  - a browser test: add a link on the Settings tab and see it in the organizations list

## Task 8: Docs

- README: a new "Syncing organizations from external systems" section (single, bulk and CSV, with lookups, the owner rules and the permission), plus routes and tools in the tables.
- Also update the CHANGELOG, the Boost skill section, the spec decisions and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- **Workbench:**
  1. `PUT /roster/organizations/external/erp/C-100` with `{name: "Initech", account_number: "A-42", domains: ["initech.test"]}` creates an ownerless organization.
  2. Repeating it with a new name updates it.
  3. `GET /roster/organizations?source=erp&account_number=A-42` finds it.
  4. A bulk call with one bad record returns per-record errors.
  5. In Atrium, the Settings tab shows the link; adding a `crm` link works; the CSV import of organizations previews and applies.
