# Roster — Phase 2, Feature 3a: SSO (OIDC + SAML)

## Context

Next Phase 2 roadmap item: SSO/SCIM. It ships in two slices; **this one is SSO login**, and SCIM provisioning follows as its own spec, reusing these connections. Organizations bring their own identity provider (Okta, Entra ID, Google Workspace, Auth0, ADFS …).

This builds on:
- organizations and verified domains: `Models/Organization`, `OrganizationDomain`, `JoinOrganizationsByDomainAction`
- `CreateUserAction` and `ManagesMemberships::join()` (with the default member role)
- `Support/Users`, `Access/*`, the audit recorder and redaction (`Audit/Snapshots.php`)
- the web-route pattern (`routes/web.php`, `Http/Web/*`)

## Decisions (from shaping)

- **Protocols:** OIDC and SAML 2.0, through **Laravel Socialite**.
  - **SAML** uses `socialiteproviders/saml2` (LightSAML: XML signature validation).
  - **OIDC** uses a small Roster Socialite driver (`Sso\OidcProvider`, extends Socialite's `AbstractProvider`), because no generic SocialiteProviders OIDC driver exists:
    - discovery from `{issuer}/.well-known/openid-configuration` (cached)
    - authorization code flow with state (Socialite) and a **nonce**
    - **id_token verified with `firebase/php-jwt` against the IdP's JWKS**: signature, `iss`, `aud`, `exp`, `nonce`
  - We never parse or verify tokens or XML by hand.
- **Optional dependencies:** `laravel/socialite`, `socialiteproviders/saml2` and `firebase/php-jwt` go in `suggest`, and in `require-dev` for Roster's tests.
  - Connections can be configured without them, but login refuses with a clear "install …" error.
  - SSO routes register only when they're installed (`Sso\Sso::available()`).
- **Connections belong to an organization.** Each organization can have SSO connections. A user is routed to one:
  - by **email domain** (the org's verified domains: `/roster/sso?email=…`), or
  - by the connection's own link (`/roster/sso/{connection}`).
- **Just-in-time provisioning** (per connection `jit`, default on): an unknown user signing in through the IdP is created and joined to the organization, **only when their email domain is one of the organization's domains**. Their email is marked verified, because the IdP is authoritative for the domain.
- **Account linking:**
  - An existing account with the same email is auto-linked **only when the domain belongs to the connection's organization** (the IdP is trusted for that domain).
  - Otherwise the login is refused, and the user links from their account while signed in (`POST /roster/sso/{connection}/link` → IdP → callback).
  - Identities are stored as `(connection, subject)` pairs, so later logins match by IdP subject, not email.
- **Optional enforcement** (per connection `enforced`): users whose email domain belongs to the organization must use SSO.
  - Roster has no password login, so it provides:
    - `Roster::ssoRequiredFor(string $email): ?SsoConnection`
    - a validation rule `RefactorCircus\Roster\Rules\NotSsoEnforced` for the app's login form, which fails with a link to the SSO login
  - Super-admins are exempt, so you can't lock yourself out.
- **Inactive users** (suspended/deactivated) are refused at the SSO callback.
- **Secrets:**
  - Connection config is stored with Laravel's `encrypted:array` cast.
  - `client_secret` is never serialized by any surface (shown as `"set"` or `null`).
  - The audit log redacts nested secret keys (`client_secret`, plus any key in `roster.audit.redact`). Snapshots now redact inside arrays.
- **Permissions:**
  - `roster.sso.view` and `roster.sso.manage` (organization scope; the `admin` role gets them through the org-wide rule).
  - Unlinking your own identity is always allowed. Unlinking anyone's needs `roster.sso.manage` in that organization.
- **Audit:** connection create/update/delete, `sso.logged_in` (with method: `jit` / `linked` / `identity`), `sso.linked` and `sso.unlinked` come from the Action events. Login failures are recorded as app-source `sso.login_failed` entries with a reason.
- **Parity:** `SsoLoginAction` and `LinkSsoIdentityAction` run the browser redirect/callback dance, so they're web-only. They become parity exceptions (`http`, `mcp`) with reasons. Connection management, identity listing and unlinking are on every surface.

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-sso/` with `plan.md`, `shape.md`, `standards.md` and `references.md`.

## Task 2: Dependencies, storage, config

- **composer:**
  - `suggest`: `laravel/socialite`, `socialiteproviders/saml2`, `firebase/php-jwt`
  - `require-dev` the same, then `composer update`
- **Migration** `2026_01_01_000006_create_roster_sso_tables.php`:
  - `roster_sso_connections`: ulid, `organization_id` FK cascade, `name`, `slug` unique, `protocol` (`oidc|saml`), `config` text (encrypted json), `jit` bool (true), `enforced` bool (false), `enabled` bool (true), timestamps.
  - `roster_sso_identities`: ulid, `connection_id` FK cascade, `user_id` (UserKey), `subject`, `email`, `last_login_at`, timestamps, unique(`connection_id`, `subject`), index(`user_id`).
- **Models:**
  - `SsoConnection`: `organization`, `identities`; `config` cast `encrypted:array`; `oidc()` / `saml()` accessors for typed config.
  - `SsoIdentity`: `connection`, `user`.
  - Both final, HasUlids, with factories.
- **Config `sso`:**
  - `redirect` '/'
  - `discovery_cache_seconds` 3600
  - `routes_prefix` 'roster/sso'
  - `middleware` ['web']
  - Banner covering the security notes (HTTPS required for callbacks, trusted domains).
- **Built-ins:** `roster.sso.view` and `roster.sso.manage`.
- **`Snapshots::attributes()`** redacts inside array values. `ALWAYS_REDACTED` gains `client_secret`.

## Task 3: Protocol layer (`src/Sso/`)

- `Sso.php`:
  - `available(): bool` (`class_exists` for Socialite, Saml2 and JWT)
  - `provider(SsoConnection): Laravel\Socialite\Contracts\Provider`, which builds the driver at runtime with the connection's config and `redirectUrl = route('roster.sso.callback', $connection)`
- `OidcProvider.php`: discovery (cached), `getAuthUrl` with nonce (session), `getTokenUrl`, `user()` override that verifies the id_token via `JWK::parseKeySet($jwks)` + `JWT::decode`, then checks `iss`, `aud`, `nonce` and `exp`, and maps claims (`sub`, `email`, `email_verified`, `name`).
- `SamlConfig.php`: maps a connection to the `socialiteproviders/saml2` config (IdP metadata URL **or** entity ID + SSO URL + x509 certificate; SP entity ID and ACS = callback route). `metadata()` gives the SP metadata XML for the `/metadata` route.
- `IdentityClaims.php`: a readonly DTO (subject, email, name, emailVerified, raw).

## Task 4: Actions

| Action | Notes |
|---|---|
| `ListSsoConnectionsAction` | `organization` filter |
| `ShowSsoConnectionAction` | |
| `CreateSsoConnectionAction` | rules per protocol: OIDC needs `issuer` (https URL), `client_id`, `client_secret`; SAML needs `metadata_url` or (`entity_id`, `sso_url`, `certificate`); plus `name`, `slug?`, `jit`, `enforced`, `enabled`. A non-https issuer or URL is refused unless the app is local. |
| `UpdateSsoConnectionAction` | a blank `client_secret` keeps the old one |
| `DeleteSsoConnectionAction` | |
| `SsoLoginAction` (web) | connection + `IdentityClaims`. Order: identity by subject, then trusted-domain link, then JIT. Then ensure membership, refuse inactive users, update `last_login_at`. Returns `[user, method]`. Failures throw field-keyed ValidationException (`sso`). |
| `LinkSsoIdentityAction` (web) | the signed-in user + claims; refuses a subject already linked to someone else |
| `ListSsoIdentitiesAction` | by user or organization |
| `UnlinkSsoIdentityAction` | |

Plus:
- `Roster::ssoRequiredFor($email)`
- `Rules/NotSsoEnforced`
- `RecordAuditEventAction` used for `sso.login_failed`

## Task 5: Surfaces

- **Web** (`routes/sso.php`, loaded when `Sso::available()`), `Http/Web/SsoWebController`:
  - `GET roster/sso?email=` (discover → redirect)
  - `GET roster/sso/{connection}` (redirect to IdP)
  - `GET|POST roster/sso/{connection}/callback` (OIDC GET, SAML POST ACS; CSRF-exempt for the SAML POST only, with the signed assertion as protection)
  - `GET roster/sso/{connection}/metadata` (SAML SP metadata)
  - `POST roster/sso/{connection}/link` (auth)
  - Errors go back to the redirect page with a flashed error.
- **HTTP API:**
  - `organizations/{o}/sso-connections` index/store
  - `sso-connections/{connection}` show/update/destroy
  - `users/{user}/sso-identities` index
  - `DELETE sso-identities/{identity}`
  - Resources hide secrets.
- **MCP:** a tool per management Action, plus list-identities and unlink.
- **Atrium:**
  - An "SSO" tab on the organization page: connections list, create/edit form (protocol switch), the copyable callback/metadata URLs, and a "Test sign-in" link.
  - The user page gets an SSO identities card (unlink).
- **Parity exceptions:** `SsoLoginAction` and `LinkSsoIdentityAction` → `http`, `mcp`, with reasons.

## Task 6: Tests

- **`SsoLoginActionTest`:** identity match, trusted-domain link, untrusted email refused, JIT on/off, JIT domain check, inactive refused, membership ensured, default role, `email_verified_at` set, `last_login_at`, audit `sso.logged_in` with its method.
- **`OidcFlowTest`** (`Http::fake` discovery, JWKS, token endpoint; an RSA key generated in the test, id_token signed with php-jwt):
  - happy path end to end through the routes
  - tampered signature, wrong `aud`, wrong `nonce`, expired token, and a bad state are each refused
- **`SamlConfigTest`:** config mapping (metadata-URL vs manual cert), SP metadata route XML (entity ID, ACS), the callback wiring with a mocked Socialite provider returning a user.
  - A full signed SAML assertion isn't built in tests: signature checks belong to LightSAML.
- **`SsoLinkTest`:** link while signed in; a subject already linked elsewhere is refused.
- **Enforcement:** `ssoRequiredFor`, the `NotSsoEnforced` rule, super-admin exemption.
- **Secrets:** `client_secret` absent from every resource; audit entries redact it; the DB column is encrypted (raw value ≠ plaintext).
- **Availability:** `Sso::available()` false → routes not registered and a clear error (simulated with a config/flag seam).
- **Matrices:** authorization (new routes/tools), parity exceptions, docs test.
- **Browser:** the org SSO tab creates an OIDC connection and shows the callback URL.

## Task 7: Docs

README SSO section:
- IdP setup per protocol (callback/ACS and metadata URLs), connection fields
- JIT and linking rules, enforcement and the login-form rule
- optional dependencies install line, security notes

Plus CHANGELOG, Boost skill, the spec's implementation decisions, the roadmap ("SSO ✅, SCIM next"), and the memory note.

## Verification

- `composer test` and `composer test:browser` are green.
- The OIDC end-to-end test passes against the faked IdP.
- Workbench: create an OIDC connection for Acme in Atrium (any issuer). The callback URL is shown, and `/roster/sso?email=ada@acme.test` redirects to the issuer's authorize URL. The SAML `/metadata` returns XML.
