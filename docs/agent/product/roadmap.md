# Product Roadmap

## Phase 1: MVP ✅ (completed 2026-10-01)

- ✅ **Users + profiles** — user CRUD, profile fields, status (active/suspended); works on the host app's User model.
- ✅ **Organizations, teams, members** — organizations, teams, memberships, invitations, switching current team.
- ✅ **Roles & permissions** — scoped roles and permissions, policies, gate integration.
- ✅ **Surfaces** — every Action exposed via HTTP API, MCP tools, and Atrium dashboard screens (parity enforced by tests); Cortex integration, rate limiting, browser tests, workbench demo.

## Phase 2: Post-Launch ✅ (completed 2026-10-01)

- ✅ **Audit log** — record who changed users, roles, and memberships; activity feed; host app events; hash-chained.
- ✅ **Impersonation** — admins impersonate users, scoped and logged.
- ✅ **SSO / SCIM** — SAML/OIDC/Entra ID login and SCIM 2.0 provisioning per organization.
- ✅ **CSV import/export** — bulk user/member import and export via `refactor-circus/impex`.

## Phase 3: Integrations

- **Soft deletes** — recoverable user and organization deletes, restore, delete permanently, optional purge after N days.
- **User approval** — configurable starting status (self-registration, admin create, per-organization SSO/SCIM) with a pending status, approve/reject and notifications.
- **Organization sync** — create and update organizations from external systems (ERP, CRM) with per-source external IDs and account numbers; upsert, bulk, and CSV.

Specs and decision logs: `agent-os/specs/`.
