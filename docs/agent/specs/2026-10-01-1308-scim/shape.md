# SCIM 2.0 Provisioning — Shaping Notes

## Scope

Phase 2, feature 3b. A SCIM 2.0 server per organization: identity providers create, update and deprovision members (Users) and teams (Groups). It's built as a protocol adapter over the existing Actions, so guards, events and audit apply unchanged.

## Decisions

- **Resources:**
  - SCIM **Users** are an organization's members. Create, read, replace (PUT), PATCH and deprovision.
  - SCIM **Groups** are that organization's **teams**. Create, rename, membership sync and delete.
- **Deprovisioning** (`active: false` via PUT/PATCH, or DELETE):
  - **Remove the membership** from that organization; team seats and org roles go with it, through `RemoveMemberAction`.
  - **Deactivate the account only if** SCIM created it *and* it now belongs to no other organization.
  - **Never hard-delete.**
  - `active: true` restores the membership (and reactivates an account SCIM had deactivated).
  - The owner can't be deprovisioned: 409 with a clear `detail`.
- **Authentication: per-organization bearer tokens.**
  - Admins issue SCIM tokens for an organization. Each is shown **once**, stored as a sha256 hash, with an optional expiry, revocable, and tracks last use.
  - The base URL is `/scim/v2/{organization}`, and a token only works for its own organization (a mismatch returns 401 and leaks nothing).
  - Managing tokens needs the new permission `roster.scim.manage` (org scope; part of `admin`).
- **Linking to SSO:** a token may name one of the organization's SSO connections. The SCIM `externalId` is then stored as an `SsoIdentity` subject for that connection, so a later SSO sign-in lands on the same account. Map it in the IdP: Okta user id ↔ OIDC `sub`; Entra objectId ↔ Graph `id`.
- **Account matching on create:**
  1. an existing SCIM mapping by `externalId`;
  2. else an existing account with the email, **only if the organization owns the domain** (`SsoConnection::trusts()`-style rule via `OrganizationDomain`);
  3. else a new account.
  - An email outside the organization's domains is refused (409 `uniqueness` / 400), so a SCIM token can't claim accounts it doesn't own.
- **RFC 7644 core:**
  - `/Users` and `/Groups`: list (`filter`, `startIndex`, `count`, `attributes`/`excludedAttributes` basic), get, POST, PUT, PATCH, DELETE.
  - Discovery: `/ServiceProviderConfig`, `/ResourceTypes`, `/Schemas`.
  - The SCIM error schema with `scimType`, and `application/scim+json`.
- **Filters:** `eq`, `co`, `sw`, combined with `and`, on `userName`, `externalId`, `emails.value`, `id`, `displayName` (Groups) and `members.value`. Anything else gives 400 `invalidFilter`. A small hand-written parser; no eval.
- **PATCH:** `add`, `replace` and `remove`, with paths such as `active`, `userName`, `displayName`, `name.givenName` / `name.familyName`, `emails[type eq "work"].value`, `externalId`, `members`, `members[value eq "…"]`, and no-path value objects (Entra style). Unsupported paths return 400 `invalidPath`.
- **Bulk** (`POST /Bulk`):
  - up to `roster.scim.bulk.max_operations` (100) operations within `max_payload_bytes` (1 MB)
  - `bulkId` references (`"bulkId:x"`) resolved in paths and data
  - `failOnErrors`
  - per-operation status
- **ETags:** `meta.version` = `W/"<hash of id+updated_at>"`. `ETag` headers on responses, `If-Match` on PUT/PATCH/DELETE (mismatch → 412), `If-None-Match` on GET → 304. Bulk honours `version`.
- **Audit:** the existing Action events record everything. Surface detection gains `scim`, and context gains `scim_token` {id, name}, so entries show *which* token did it. Token issue and revoke are audited too.
- **Rate limit and toggle:** routes use `['api', 'throttle:roster']` (configurable). `roster.scim.enabled` defaults to true, and a request without a valid token is denied.


## Context

- **Visuals:** None.
- **References:** see references.md.
- **Product alignment:** roadmap Phase 2 item 3, second half.

## Standards Applied

- Same set as SSO. SCIM endpoints are a protocol surface over existing Actions, so they're outside the Action parity check. The token-management Actions follow parity as usual.

## Decisions made during implementation

- **`eq` filters are exact, enforced in PHP.** `LIKE` narrows the candidates, then each is compared in full, case-insensitively, so a `_` or `%` in `userName eq "a_b@…"` can never match a different account. That would have let an IdP probe link the wrong person. `co`/`sw` are searches, where wildcards only widen results within the token's organization. Raw SQL expressions were avoided entirely (Larastan's literal-string rule agreed).
- **Echoed views write nothing.** PATCH applies operations to the current SCIM view, then replays it through the Actions. The view's `displayName` falls back to the account name, so replaying it once wrote a spurious `profile.updated`; only real changes are written now. Found in the workbench smoke test.
- **A changed name part drops `name.formatted`**, so `name.familyName` patches take effect instead of being masked by the stale formatted name.
- **Group membership sync only touches SCIM-managed seats.** Seats an admin gave someone in Atrium, for a user the IdP doesn't manage, are left alone.
- **SCIM resource ids are the mapping rows' ULIDs** (`roster_scim_users.id`, `roster_scim_groups.id`), not user or team keys. A deleted SCIM user's row goes away (GET returns 404), but the account and history remain.
- **Errors use the SCIM error schema throughout.** The auth middleware renders `ScimException`s, so 401, 404, 409, 412 and 413 all carry `schemas`, `status` and `scimType`. Responses are `application/scim+json`, with `ETag` and, on 201, `Location`.
- **The SCIM protocol isn't in the Action parity check**, because it's an adapter over existing Actions. The token-management Actions do follow parity (HTTP, MCP, Atrium).
- **Seen in the workbench:** create user, then group (a team appears), then Okta-style deprovision. The account was deactivated (SCIM-created, orphaned), a wrong token got 401, and every change is in the audit log with surface `scim` and the token name.
