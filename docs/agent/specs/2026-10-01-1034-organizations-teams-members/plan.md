# Roster — Feature 2: Organizations, Teams, Members

## Context

Roster roadmap, Phase 1, feature 2 (feature 1, Users + profiles, is done: spec `agent-os/specs/2026-10-01-1006-users-profiles/`). This slice adds the tenancy layer: organizations, the teams inside them, memberships, email invitations, optional domain auto-join, and a per-user current org/team context. Feature 3 (roles & permissions) builds on these memberships, so this slice keeps roles to an owner flag only.

Patterns to reuse from feature 1:
- Action shape: `src/Actions/*`, `Actions/Concerns/*`
- Events: `src/Events/Action/*` and the `Contracts/Action{Starting,Finished}Event` contracts
- HTTP: `src/Http/Request.php`, `Http/Requests/UserRequest.php`
- MCP: `src/Mcp/Request.php`, `Mcp/Requests/UserMcpRequest.php`, `RosterServer::TOOLS`
- Atrium: `src/Atrium/RosterPlugin.php`, `Http/Ui/UserUiController.php`, `resources/views/ui/*`
- Users: `src/Support/Users.php` (`findOrFail`, `profile`, `column`)
- Test helpers: `tests/Pest.php` (`user()`, `mcpTool()`, `parityGaps()`)

## Decisions (from shaping)

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

## Task 1: Save spec documentation

`agent-os/specs/2026-10-01-HHMM-organizations-teams-members/` (timestamp at save) containing:
- `plan.md`
- `shape.md` (the decisions above)
- `standards.md`: the same set as feature 1, plus `runtime-exceptions` for the invitation token
- `references.md`: feature 1 files as the primary reference

## Task 2: Schema, models, config

- **Migration** `2026_01_01_000002_create_roster_organization_tables.php`:
  - `roster_organizations`: ulid, name, slug unique, `owner_id` (user key type), `personal` bool, `auto_join` bool, timestamps.
  - `roster_organization_domains`: ulid, `organization_id` FK cascade, `domain` unique.
  - `roster_memberships`: ulid, `organization_id` FK cascade, `user_id` (key type), `source` (`direct|invitation|domain|personal`), timestamps, unique(org, user).
  - `roster_teams`: ulid, `organization_id` FK cascade, name, slug, timestamps, unique(org, slug).
  - `roster_team_members`: ulid, `team_id` FK cascade, `membership_id` FK cascade, unique(team, membership). Leaving the org therefore drops team seats automatically.
  - `roster_invitations`: ulid, `organization_id` FK cascade, email, `token_hash` unique, `teams` json, `invited_by` nullable, `expires_at`, `accepted_at`, `declined_at`, `revoked_at`, timestamps, index(org, email).
  - Alter `roster_profiles`: add `current_organization_id` and `current_team_id`, nullable foreign ULIDs with `nullOnDelete`.
- **Models:** final and HasUlids, with factories. `Organization` (owner, members, memberships, teams, domains, invitations), `Membership`, `Team`, `TeamMember`, `OrganizationDomain`, `Invitation` (scopes `pending()`, `isExpired()`). `Profile` gains `currentOrganization` / `currentTeam`.
- **Owner key column** uses a shared helper on the migration side: a `Support\UserKey::column($table, $name)` match on `roster.users.key_type`, reused by migrations 000001 and 000002.
- **`HasRoster` trait:** add `rosterOrganizations()` (BelongsToMany through `roster_memberships`). Register the same dynamic relation for trait-less models in the provider.
- **Config additions:**
  - `organizations.personal` (false)
  - `invitations.expires_after_days` (7), `invitations.redirect` (`/`), `invitations.accept_url` (null), `invitations.middleware` (`['web', 'auth']`)
  - Each has a documented banner, with security notes on the accept route.

## Task 3: Actions (`src/Actions/`), with events and guards

| Group | Actions |
|---|---|
| Organizations | `ListOrganizationsAction` (search, `user` filter), `ShowOrganizationAction`, `CreateOrganizationAction` (name, optional slug, owner; owner becomes a member), `UpdateOrganizationAction` (name, slug, auto_join, domains[] replace), `DeleteOrganizationAction` (guard: personal), `TransferOwnershipAction` (target must be a member) |
| Members | `ListMembersAction`, `AddMemberAction`, `RemoveMemberAction` (guard: owner; clears the current context pointing at the org) |
| Teams | `ListTeamsAction`, `ShowTeamAction` (with members), `CreateTeamAction`, `UpdateTeamAction`, `DeleteTeamAction`, `AddTeamMemberAction` (guard: org member), `RemoveTeamMemberAction` |
| Invitations | `ListInvitationsAction` (status filter), `CreateInvitationAction` (guards: already a member, pending invite exists, teams belong to the org; sends `InvitationNotification`), `RevokeInvitationAction`, `AcceptInvitationAction` (token + user; guards: expired/revoked/used, email mismatch; joins the org and teams in one transaction, source `invitation`), `DeclineInvitationAction` |
| Context | `SwitchContextAction` (user, organization slug, optional team slug; guards: membership, team in org) |
| Domains | `JoinOrganizationsByDomainAction` (user; joins every `auto_join` org owning the email's domain; no-op if the email isn't verified) |

Supporting pieces:
- `src/Support/Slugs.php`: unique slug from a name, scoped for teams.
- `src/Support/InvitationTokens.php`: generate, hash, find.
- `src/Exceptions/InvalidInvitationException.php`, for a token not found, per `runtime-exceptions`.
- `src/Notifications/InvitationNotification.php`: mail with the signed accept URL.
- `src/Listeners/JoinOrganizationsOnVerified.php`: registered in the provider.
- `src/Roster.php` service: `organization(Model $user)` and `team(Model $user)` context resolution.
- `src/Http/Middleware/EnsureUserHasOrganization.php`, aliased as `roster.organization`.
- Feature 1 updates:
  - `CreateUserAction` creates the personal org when configured and runs domain join when already verified.
  - `DeleteUserAction` guards against owning a non-personal org, then deletes the user's memberships and personal org.
  - `UserResource` gains `current_organization` / `current_team` slugs.

## Task 4: HTTP API (`routes/roster.php`, `roster.` names)

- `organizations` index/store; `organizations/{organization:slug}` show/update/destroy; `POST .../transfer`
- `organizations/{o}/members` index/store; `DELETE .../members/{user}`
- `organizations/{o}/teams` index/store; `.../teams/{team}` show/update/destroy, with `{team}` resolved by slug within `{o}`; `POST .../teams/{team}/members`, `DELETE .../teams/{team}/members/{user}`
- `organizations/{o}/invitations` index/store; `DELETE .../invitations/{invitation}` (revoke)
- `POST invitations/{token}/accept|decline` (acts as the authenticated user; 401 for guests)
- `PUT users/{user}/context`, `POST users/{user}/domain-join`

Code: requests per Action with `OrganizationRequest` / `TeamRequest` bases, controllers (`OrganizationController`, `MemberController`, `TeamController`, `TeamMemberController`, `InvitationController`, `UserContextController`), and resources (`OrganizationResource`, `MemberResource`, `TeamResource`, `InvitationResource`, which never exposes the token).

## Task 5: MCP

One tool and one request per Action, with `OrganizationMcpRequest` / `TeamMcpRequest` model-binding bases (slug arguments) and shared schema traits (`DescribesOrganization`, `DescribesTeam`). Add them all to `RosterServer::TOOLS` and update the server instructions (tenancy model, invitation lifecycle).

## Task 6: UI surfaces

- **Atrium:**
  - Nav item "Organizations".
  - Screens: index/create, and an org show page with tabs for Members (add/remove/transfer), Teams (create; team page with members), Invitations (invite with team checkboxes, revoke) and Settings (name, slug, auto-join, domains, delete).
  - The user show page gains a Memberships card (orgs and teams, a context switcher, "run domain join").
  - Widgets: org count and pending invitations.
  - Search over orgs.
  - Code: `Http/Ui/OrganizationUiController`, `TeamUiController`, `InvitationUiController`; extend `UserUiController`.
- **Web (user-facing):** `src/Http/Web/InvitationWebController` on a signed route `roster.invitations.show` (GET page, POST accept/decline) under `roster.invitations.middleware`. A minimal standalone Blade view (no Atrium layout), `resources/views/invitations/show.blade.php`.

## Task 7: Tests

- **Actions:** `OrganizationActionsTest`, `MemberActionsTest`, `TeamActionsTest`, `InvitationActionsTest` (expiry, revoke, reuse, email mismatch, team pre-assign, notification faked), `ContextActionsTest` (switch, fallback after removal), `DomainJoinTest` (verified only, `Verified` event listener, auto_join off, domain uniqueness).
- **Feature 1 regressions:** personal org on create, delete-user guards.
- **HTTP:** `tests/Feature/Http/{Organizations,Teams,Invitations}HttpTest.php`, including 401 on accept as a guest.
- **MCP:** `tests/Feature/Mcp/{Organization,Team,Invitation}ToolsTest.php` with HTTP parity.
- **UI:** `tests/Feature/Ui/OrganizationPagesTest.php`; `tests/Feature/Web/InvitationPageTest.php` (signed URL required, auth redirect, accept/decline).
- **Middleware:** `roster.organization`.
- **Modes:** ULID mode with organizations, to check the owner/user key column types.
- **Arch:** `parityGaps()` UI surface = `src/Http/Ui` + `src/Http/Web`.

## Task 8: Docs

- README: organizations and teams, invitations (mail, accept route, config), domain join (verified-only warning), context and middleware, personal orgs, API table.
- CHANGELOG.
- Boost skill (`package-generate-skill`).
- Spec `shape.md`: implementation-time decisions.
- Memory progress note.

## Verification

- `composer test` with the Herd php84 PATH shim: PHPStan, Pint, type coverage 100%, Pest all green.
- Parity: temporarily removing one MCP request or UI reference fails the arch test.
- End to end in tests: invite → `Notification::fake` captures the URL → visit the signed URL as a matching user → accept → membership and team seats exist → context switch → `roster.organization` middleware passes.
- Workbench: `composer serve`, then `/atrium`. Create an org, add a team, invite, and open the accept link from the log mailer.
