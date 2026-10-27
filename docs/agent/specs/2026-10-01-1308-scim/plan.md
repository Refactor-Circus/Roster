# Roster — Phase 2, Feature 3b: SCIM 2.0 Provisioning

## Context

Second half of the roadmap's "SSO / SCIM" item. SSO shipped in spec `2026-10-01-1251-sso`. Identity providers (Okta, Microsoft Entra ID, OneLogin, JumpCloud…) push people and groups into an organization through **SCIM 2.0** (RFC 7643/7644), and remove them when they leave.

SCIM is a **protocol adapter over existing Actions**, so every SCIM change gets the same guards, events and audit trail as the other surfaces:
- users: `CreateUserAction`, `UpdateUserAction`, `UpdateProfileAction`, `DeactivateUserAction`, `ReactivateUserAction`
- membership: `ManagesMemberships::join()`, `RemoveMemberAction`
- teams: `CreateTeamAction`, `UpdateTeamAction`, `DeleteTeamAction`, `AddTeamMemberAction`, `RemoveTeamMemberAction`
- SSO linking: `SsoIdentity`, `SsoConnection` (`src/Models`)

## Decisions (from shaping)

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

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-scim/` with `plan.md`, `shape.md`, `standards.md` and `references.md` (RFC 7643/7644 sections; Okta and Entra SCIM request shapes).

## Task 2: Storage and config

- **Migration** `2026_01_01_000007_create_roster_scim_tables.php`:
  - `roster_scim_tokens`: ulid, `organization_id` FK cascade, `name`, `token_hash` unique, `sso_connection_id` nullable FK nullOnDelete, `created_by` (UserKey, nullable), `last_used_at`, `expires_at`, `revoked_at`, timestamps.
  - `roster_scim_users`: ulid (the SCIM `id`), `organization_id` FK cascade, `user_id` (UserKey), `external_id` nullable, `created_by_scim` bool, `deactivated_by_scim` bool, `active` bool, timestamps, unique(org, user), index(org, `external_id`).
  - `roster_scim_groups`: ulid (the SCIM `id`), `organization_id`, `team_id` FK cascade (unique), `external_id` nullable, timestamps.
- **Models** `ScimToken`, `ScimUser`, `ScimGroup` (final, HasUlids, with factories).
- **`MembershipSource::Scim`.**
- **Config `scim`:** `enabled`, `prefix` 'scim/v2', `middleware` ['api', 'throttle:roster'], `bulk.max_operations` 100, `bulk.max_payload_bytes` 1048576, `default_count` 100, `max_count` 500. Banner with security notes.
- **Built-ins:** `roster.scim.manage`.

## Task 3: Protocol layer (`src/Scim/`)

- `ScimException`: status + `scimType` + detail; renders the SCIM error JSON.
- `Filter/FilterParser` → `Filter/Condition[]`; `applyTo(Builder, map)`; `invalidFilter` on anything else.
- `Patch/PatchApplier`: applies operations to a mutable "SCIM view" array, then hands the diff to the Actions.
- `UserMapper`: User model ↔ SCIM User JSON (`id` = ScimUser id, `userName`, `name`, `displayName`, `emails`, `active`, `externalId`, `groups`, `meta`). `GroupMapper`: Team ↔ SCIM Group (`members` value = ScimUser id).
- `ScimUsers` service: list, get, create, replace, patch, delete. It orchestrates the matching rules, the Actions, deprovision and restore, and SSO linking.
- `ScimGroups` service: list, get, create, replace, patch (membership add/remove/replace), delete.
- `Bulk`: runs operations through the services with bulkId resolution.
- `ETag`: compute and compare.
- `Discovery`: ServiceProviderConfig (patch, bulk, filter, etag supported; changePassword/sort false; authenticationSchemes oauthbearertoken), ResourceTypes and Schemas JSON.
- Middleware `AuthenticateScimToken`: Bearer → hash lookup, org match, not revoked or expired. It stamps `last_used_at` (throttled to once a minute) and sets `ScimContext` (token, organization) for the audit context.
- **`Audit/Surface`:** routes named `roster.scim.` give `scim`. The AuditRecorder adds `scim_token` context when ScimContext is active.

## Task 4: Token management Actions and surfaces

- **Actions:**
  - `ListScimTokensAction` (org)
  - `CreateScimTokenAction` (name, `expires_in_days?`, `sso_connection?` slug; returns `IssuedScimToken` {token model, plain token})
  - `RevokeScimTokenAction`
- **HTTP:** `organizations/{o}/scim-tokens` index/store (201 with `token` once), `DELETE scim-tokens/{token}`. Ability `roster.scim.manage` in the organization.
- **MCP:** the matching tools, with the token returned once.
- **Atrium:** an organization "SCIM" tab: the base URL to copy, a token list (name, last used, expiry, revoked), a create form that shows the token once with a warning, and revoke.
- **SCIM protocol routes** (`routes/scim.php`, `Http/Scim/*Controller`). These are a separate protocol surface, not Roster Actions, so they're not in the parity check (documented).

## Task 5: Tests

- **Okta and Entra request fixtures** (`tests/Fixtures/Scim/*.json`, real-world payload shapes): create user, PATCH `active` false, PATCH replace with no path (Entra), group create, member add/remove via path filter.
- **`ScimAuthTest`:** missing, invalid, revoked, expired and other-org tokens → 401; `last_used_at` stamped.
- **`ScimUsersTest`:**
  - CRUD
  - create matching (externalId, trusted email link, untrusted email refused, new user)
  - deprovision: membership removed; account deactivated only when orphaned and SCIM-created; never deleted
  - reactivate
  - owner protected (409)
  - PUT replace semantics
  - SSO identity link via `externalId`
- **`ScimGroupsTest`:** create → team, rename, members add/remove/replace, delete, `members.value` filter.
- **`ScimFilterTest`:** each operator and attribute, `and`, invalid → 400 `invalidFilter`, injection-ish input safe.
- **`ScimPatchTest`:** every supported path, value objects, invalid path → 400.
- **`ScimBulkTest`:** mixed operations, bulkId references, `failOnErrors` stopping, max operations/payload → 413 `tooMany`.
- **`ScimEtagTest`:** ETag headers, `If-Match` 412, `If-None-Match` 304.
- **`ScimDiscoveryTest`:** ServiceProviderConfig flags, ResourceTypes, Schemas.
- **Audit:** SCIM changes have surface `scim` and `scim_token` context; token issue and revoke are audited.
- **Token management:** HTTP/MCP/UI tests, the authorization matrix, docs and parity tests updated, and a browser test (issue a token, see it once).

## Task 6: Docs

README SCIM section: setup in Okta and Entra (base URL, token, attribute mapping, the `externalId` ↔ SSO subject tip), deprovisioning semantics, supported features and limits, security notes. Plus CHANGELOG, Boost skill, the spec's decisions, the roadmap tick, and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- Workbench: issue a SCIM token for Acme in Atrium, then `curl` the API:
  - create a user
  - PATCH it inactive: the membership is gone and the account stays active (because Ada is a member elsewhere? no — check orphan rule)
  - create a group and add the member: a team appears in Atrium
  - the audit log shows surface `scim` with the token name
