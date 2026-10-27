# SSO — Shaping Notes

## Scope

Phase 2, feature 3a: SSO login (OIDC and SAML) per organization, with just-in-time provisioning, trusted-domain account linking and optional enforcement. SCIM provisioning is the next slice (3b) and reuses these connections.

## Decisions

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


- **Added mid-implementation (user request): Microsoft Azure / Entra ID via `socialiteproviders/microsoft-azure`** as a third connection protocol, `azure`, configured with `tenant`, `client_id` and `client_secret`.
  - **A specific tenant is required** (a tenant ID or verified domain). `common`, `organizations` and `consumers` are refused, because a multi-tenant authority would let users from any tenant sign in.
  - **The email is the `userPrincipalName`, never `mail`.** Entra lets tenant admins set `mail` freely (the nOAuth account-takeover pattern), while a UPN must be on a domain the tenant verified.
  - The subject is the Graph object `id`. Identity comes from Graph `/me` after a server-side code exchange authenticated with the client secret.

## Context

- **Visuals:** None.
- **References:** see references.md.
- **Product alignment:** roadmap Phase 2 item 3 (first half).
- **Library check during shaping:** `laravel/socialite` 5.31, `socialiteproviders/saml2` 4.10 (LightSAML) and `firebase/php-jwt` 7.2 resolve against Laravel 13. No generic SocialiteProviders OIDC driver exists, hence Roster's own `OidcProvider`.

## Standards Applied

- Same set as impersonation. Two more parity exceptions (web-only login and link flows) are recorded in `tests/Pest.php`.

## Decisions made during implementation

- **Event and audit names:** `sso_connection.created/updated/deleted`, `sso_login.succeeded` (context: method `identity|linked|jit`), `sso_login.failed` (context: email, reason), `sso_identity.linked/unlinked`. These replace the planned `sso.logged_in`, so they follow the noun.verb rule every other entry uses.
- **Login failures are recorded as Roster entries** (`SsoLoginFailedActionEvent`), not app-source ones: they are Roster's own decisions.
- **Bug found by tests:** refusals raised inside the login transaction were audited and then rolled back. `SsoLoginRefused` now carries the reason out of the transaction, and the failure is recorded after the rollback.
- **The OIDC driver makes its HTTP calls through Laravel's `Http` client** (discovery, JWKS, token exchange), so tests drive a fake IdP with a real RSA key and signed tokens. Covered: forged signature, wrong audience, nonce, issuer, expiry, and state.
- **The Azure provider uses its own Guzzle client.** Tests (and apps needing a proxy) bind `roster.sso.http` to supply one.
- **SAML end-to-end assertions aren't built in tests.** Signature validation belongs to LightSAML. Tests cover the config mapping, the SP metadata XML and the CSRF-exempt ACS post, with the provider swapped through the container (`Sso` is not final for this reason).
- **Cache keys use sha256**, since Pest's security preset flags `sha1`.
- **`SsoConnection::trusts()`** is the single place that decides whether an IdP may speak for an email (the organization's domains). JIT and auto-linking both go through it.
- **Without the packages, management still works:** connections can be created, and `callback_url` is null. The sign-in routes are absent, and building a provider throws `SsoUnavailableException` with the exact `composer require` line.
