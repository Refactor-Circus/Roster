# Auth extension seams — Shaping Notes

## Scope

Give Roster public extension points so that SSO and SCIM can live in a separate package (Guardian) without Roster knowing about them. Roster's SSO/SCIM code then moves onto these seams in place. The code stays in Roster for now; the remove-sso-and-scim spec deletes it after Guardian exists.

Coupling points found (2026-10-29):

1. **Authorization scope types.** `Domains/Permission/Services/Authorizer.php:51-52` hard-codes `'sso' => SsoConnectionModel::class` and `'scim' => ScimTokenModel::class`. Needed: a way to register scoped model types, e.g. `Authorizer::scopeType('sso', Model::class)`.
2. **Membership source.** `Domains/Organization/Enums/MembershipSource::Scim` is an enum case. Needed: an extensible membership source, either a string column with registered values, or a registry of labels alongside the enum's built-in values.
3. **Audit context.** `Support/Audit.php:184-187` adds `scim_token` context from `ScimContext`. Needed: an audit context contributor seam (callbacks that add context to every Roster audit entry).
4. **Sign-in enforcement.** `Roster::ssoRequiredFor(string $email)` (`src/Roster.php:96`) answers with an `SsoConnectionModel`. Needed: a contract such as `SignInPolicy`, which other packages can register to require an external sign-in for an email. Roster asks the registered policies and holds no SSO types itself.
5. **Provisioned status.** `organizations.provisioned_status` is read by `SsoLoginAction:143` and `ScimUsers:115`. It stays a Roster organization attribute and becomes part of Roster's public API, so other packages provisioning users read it through a documented accessor.
6. **Permissions.** The `roster.sso.*` and `roster.scim.*` permissions and the `sso` / `scim` policies in `config/roster.php` (:514-542). Needed: other packages register permissions into Roster's catalogue (with their own prefix, e.g. `guardian.sso.manage`) and get the same `ScreenAccess` / `@rosterCan` checks.
7. **MCP tools on RosterServer.** SSO/SCIM tools are listed in `src/Mcp/RosterServer.php`. They will live on Guardian's own MCP server, so no Roster seam is needed. Recorded here so it isn't missed.

## Decisions

- D-001: these seams land and are used by Roster's in-tree SSO/SCIM code first, so they are proven before extraction. No behaviour changes.
- D-002: the seams are package-agnostic. They are named for what they do (scope types, membership sources, audit context, sign-in policy, permission catalogue), never "Guardian", so any auth package can use them.
- D-003: spec writing decides whether `MembershipSource` becomes a string-backed value with registered labels, given the stored `source` values (stored-identifiers standard).

## Context

- **Order:** independent of the Atrium registry, so it can run in parallel. Must merge before the Guardian spec (root `docs/agent/specs/2026-10-29-2038-guardian-package`).
- **Product:** Roster stays user management; auth and security move to Guardian.
- **References:** see `references.md`.

## Standards

- architecture/public-api: every seam is new public API with documented stability.
- architecture/dependencies: Roster core never imports an integration, and packages extend it through contracts.
- architecture/container-bindings: seams are contracts with registrations and the right binding lifetime.
- architecture/service-providers: registration from `boot()`.
- models/stored-identifiers: `membership.source` and any stored type keys.
- http/authorization: permission catalogue registration and policy wiring.
- testing/test-layers: SSO/SCIM feature tests keep passing unchanged through the seams.
