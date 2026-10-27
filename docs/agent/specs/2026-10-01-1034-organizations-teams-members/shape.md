# Organizations, Teams, Members — Shaping Notes

## Scope

Phase 1 MVP, feature 2. The tenancy layer: organizations with teams inside them, memberships, email invitations (self-service accept), optional per-org domain auto-join, and a per-user current org/team context. Roles stay out (feature 3); only an owner flag exists.

## Decisions

- **Hierarchy:** an organization is the tenant, and teams sit inside one organization (flat, no nesting). Team members must be organization members.
- **Joining:**
  - An admin adds an existing user directly.
  - **Email invitations** go to an organization and can pre-assign teams. They have a token, an expiry, and accept/decline/revoke, and they work for people who aren't registered yet.
  - **Domain auto-join** is an optional per-org setting (`auto_join` off by default, plus a list of domains). A domain belongs to at most one org.
- **Auto-join fires on verified email only.** A listener on Laravel's `Illuminate\Auth\Events\Verified`, plus on create when `email_verified_at` is already set. Models without MustVerifyEmail never auto-join. This blocks registering `x@acme.com` to get into Acme.
- **Invitation acceptance:**
  - The mail links to a built-in signed web route, `roster/invitations/{token}`, behind `['web', 'auth']` (configurable).
  - A logged-in user whose **email matches** the invitation can accept or decline it, then is redirected to `roster.invitations.redirect`.
  - Guests go through the host's login or registration first (the intended URL is preserved).
  - Tokens are stored as a sha256 hash, and the URL can be overridden with `roster.invitations.accept_url`.
- **Many organizations per user.** The current org and team are stored on `roster_profiles` (`current_organization_id`, `current_team_id`). A `SwitchContextAction` changes them. `Roster::organization($user)` / `Roster::team($user)` resolve the context; they fall back to the user's first org when the stored one is gone or the user has left it. A `roster.organization` middleware returns 403 to a user with no organization.
- **Roles: an owner flag only** (`roster_organizations.owner_id`).
  - Guards: the owner can't be removed, and ownership moves only through `TransferOwnershipAction` to an existing member.
  - A user who owns a non-personal org can't be deleted.
  - A personal org can't be deleted on its own, and it's removed with its owner.
- **Personal org:** off by default. When `roster.organizations.personal` is true, `CreateUserAction` creates one owned by the new user.
- **Identity:** organizations and teams use slugs (team slugs are unique within their org). Invitations, which have no human name, use their ULID. Users keep their route key.
- **Surfaces:** every Action is on HTTP, MCP and a UI. The parity test's UI surface becomes `src/Http/Ui` (Atrium admin) **plus** `src/Http/Web` (the user-facing invitation page), because accept and decline are self-service, not admin actions.
- **Still no authorization** (it arrives in feature 3). HTTP and MCP stay off by default. Accept and decline require an authenticated user because they act as that user.


## Context

- **Visuals:** None.
- **References:** feature 1 implementation (spec `2026-10-01-1006-users-profiles`), the primary pattern source.
- **Product alignment:** roadmap Phase 1 item 2; headless Action-first with full surface parity.

## Standards Applied

- Same set as feature 1 (see standards.md), plus backend/runtime-exceptions for invalid invitation tokens and backend/slug-references for organization/team slugs.

## Decisions made during implementation

- **The web invitation page lives at `/roster/invitation/{token}` (singular)**, so it never collides with the JSON API's `/roster/invitations/{token}/accept` when both use the `roster` prefix. Its routes are always registered, even with the API off, so emailed links keep working.
- **Only the GET page is signed.** The accept/decline POSTs are protected by auth, the secret token, and the email-match guard; signing the forms added nothing.
- **Invalid tokens are a domain guard (`token` 422)** inside the Actions. `InvalidInvitationException` stays as the typed runtime exception from `InvitationTokens::find()` for host code, and the web page maps it to a 404.
- **Accept/decline over HTTP returns 401 for guests** (`InvitationResponseRequest::failedAuthorization`). Over MCP it returns "Unauthorized.".
- **Team seats are keyed by membership id** (`roster_team_members.membership_id`), so removing a member cascades their seats with no extra bookkeeping.
- **Team payloads always include members** (create, update and show all load `memberships.user`), which keeps the MCP and HTTP payloads identical.
- **Context resolution falls back to the oldest membership** and ignores a stored team the user no longer sits on. Removing a member, deleting an organization or team, or removing a seat also clears stale stored pointers explicitly, since not every host database enforces `nullOnDelete`.
- **Email matching is case-insensitive.** Invitation emails are stored lower-cased; the "already a member" check uses `whereLike` plus an exact `strcasecmp`, so `_` and `%` in addresses can't produce false matches.
- **The workbench User now implements `MustVerifyEmail`**, so domain join can be exercised. The factory marks users verified by default.
- **Parity "ui" surface = `src/Http/Ui` + `src/Http/Web`.** Removing the web controller was verified to fail the arch test.
