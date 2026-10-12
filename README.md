<div align="center">
    <img src="art/icon.png" width="160" alt="Roster icon">
    <h1>Roster</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/refactor-circus/roster"><img src="https://img.shields.io/packagist/v/refactor-circus/roster.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/roster"><img src="https://img.shields.io/packagist/php-v/refactor-circus/roster.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/refactor-circus/roster"><img src="https://badge.laravel.cloud/badge/refactor-circus/roster?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/Refactor-Circus/roster/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/refactor-circus/roster/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/refactor-circus/roster"><img src="https://img.shields.io/packagist/dt/refactor-circus/roster.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Headless, Action-first user management for Laravel. Every operation is a single Action class, reachable from PHP, a JSON API, an MCP server, and the [Atrium](https://github.com/Refactor-Circus/Atrium) dashboard.

- User create, update, delete and listing on **your own** user model
- Profiles (display name, avatar, timezone, locale, bio, meta) in a side table, so your `users` table is never altered
- An active / suspended / deactivated lifecycle with a `roster.active` middleware
- Organizations with teams, members, email invitations, optional domain auto-join, and a per-user current organization/team
- Roles and permissions at global, organization and team scope, resolved through Laravel's Gate (`$user->can()`, `@can`)
- Every change recorded in the suite-wide audit log of [`refactor-circus/keen`](https://github.com/Refactor-Circus/Keen), when installed, with each record's history on its Atrium page
- Impersonation through one-time links, with time limits, a banner, blocked abilities and a full audit trail (with refactor-circus/keen)
- Single sign-on per organization: OpenID Connect, SAML 2.0 and Microsoft Entra ID, with just-in-time accounts and optional enforcement
- SCIM 2.0 provisioning per organization: identity providers create, update and deprovision members and teams

## Installation

```bash
composer require refactor-circus/roster
```

Publish the config and migrations, then migrate:

```bash
php artisan vendor:publish --tag="roster-config"
php artisan vendor:publish --tag="roster-migrations"
php artisan migrate
```

Set `roster.users.key_type` (`int`, `ulid` or `uuid`) to match your user model's primary key **before** migrating, because it decides the type of the `roster_profiles.user_id` column.

Every table name is configurable under `roster.tables`, keyed by what it holds (`organizations`, `memberships`, `team_members`, `permission_role`, ...). Rename them **before** migrating: the migrations, models, validation rules and queries all read the same keys, and a published config that lists only some tables keeps the defaults for the rest.

```php
'tables' => [
    'organizations' => 'crm_organizations',
    'memberships' => 'crm_memberships',
],
```

`users` only names the table of Roster's ready-made `UserModel` (and its opt-in migration); your own user model keeps its table. `keen:import-roster` reads the old audit log from its default table names.

The views and translations can be published with `roster-views` and `roster-lang`.

Roster stands on [`refactor-circus/keystone`](https://github.com/Refactor-Circus/Keystone), the shared runtime of the Refactor Circus package suite, which Composer installs with it. Roster's Action events implement Keystone's `ActionStartingEvent` and `ActionFinishedEvent` contracts, so one listener hears every package of the suite.

## Choosing a user mode

Roster manages whatever model `roster.users.model` points at (default `App\Models\User`).

**1. Your model with the trait (recommended).** This gives typed helpers:

```php
use RefactorCircus\Roster\Domains\User\Concerns\HasRoster;

class User extends Authenticatable
{
    use HasRoster;
}

$user->roster();          // the Profile, created on first access
$user->rosterStatus();    // UserStatus::Active | Suspended | Deactivated
$user->isRosterActive();
```

**2. Your model, untouched.** Without the trait, Roster registers the `rosterProfile` relation on the configured model at boot. Everything works the same; use `app(RefactorCircus\Roster\Support\Users::class)->profile($user)` / `->status($user)` in place of the helpers.

**3. Roster's model.** For apps without a users table, point `roster.users.model` and your auth provider at `RefactorCircus\Roster\Domains\User\Models\UserModel`, then publish its migration:

```bash
php artisan vendor:publish --tag="roster-users-migration"
```

If your users table names its columns differently, map them:

```php
'columns' => ['name' => 'full_name', 'email' => 'email_address', 'password' => 'secret'],
```

Roster finds users by email ignoring case. It looks the address up as given and lowercased first, which your email column's index serves; only an address stored with other casing falls back to a case-insensitive comparison. On PostgreSQL that comparison is `lower(email) = ?`; publish the optional migration adding the matching expression index to your users table (it does nothing on other databases):

```bash
php artisan vendor:publish --tag="roster-email-index-migration"
```

## Actions

Every operation is an Action in its domain's `Actions` namespace (`RefactorCircus\Roster\Domains\{Domain}\Actions`, e.g. `Domains\User\Actions\CreateUserAction`). An Action takes its input as a typed, immutable Data object from the domain's `Data` namespace, named after it (`CreateUserAction` takes `Domains\User\Data\CreateUserData`), never as an array.

```php
use RefactorCircus\Roster\Domains\User\Actions\{CreateUserAction, SuspendUserAction};
use RefactorCircus\Roster\Domains\User\Data\{CreateUserData, SuspendUserData};

$user = app(CreateUserAction::class)->execute(new CreateUserData(
    email: 'ada@example.com',
    name: 'Ada Lovelace',
    displayName: 'Ada',           // profile fields are accepted too
));

app(SuspendUserAction::class)->execute($user, new SuspendUserData(reason: 'Chargeback'), actor: auth()->user());
```

**Untrusted input validates itself.** `XData::from($input)` checks the input against the Data's `rules()` (throwing a `ValidationException`) and fills each typed field from its snake_case key, casting enums, numbers and booleans. Atrium and the signed pages build their Data this way. The API and MCP requests validate once, with the Data's rules among their own (`XData::rules()`), and build with `XData::fromValidated($request->validated())`, so every error comes back in one response; every surface validates with the same rules. `new XData(...)` is for values your own code vouches for, and is not validated.

```php
$user = app(CreateUserAction::class)->execute(CreateUserData::from($request->all()));

UpdateUserData::rules($user);   // the rules, for your own forms; some take context like the user being updated
```

Optional fields of an update default to `RefactorCircus\Keystone\Data\Missing`, so a field you leave out is left alone and a field set to `null` is cleared: `new UpdateUserData(name: 'Ada King')` changes the name only. Ask with `$data->has('email')`.

| Action | Does |
|---|---|
| `ListUsersAction` | Paginated list; `search` (name, email, display name), `status`, `per_page` |
| `ShowUserAction` | One user with profile |
| `CreateUserAction` | User + profile in one transaction; random password when none is given |
| `UpdateUserAction` | Name, email, password |
| `DeleteUserAction` | Deletes the user; the profile too, unless the model soft-deletes |
| `UpdateProfileAction` | Profile fields; creates the profile if missing |
| `SuspendUserAction` / `DeactivateUserAction` | Optional `reason` |
| `ApproveUserAction` / `RejectUserAction` | Accept or turn down an account awaiting approval (see below) |
| `ReactivateUserAction` | Back to active |

Business-rule failures, such as suspending yourself or suspending a user who is already suspended, throw a field-keyed `ValidationException`.

### Events

**Every Action dispatches an event before and after it runs** (`RefactorCircus\Roster\Domains\{Domain}\Events\*ActionEvent`): `UserSuspendingActionEvent` with the input, then `UserSuspendedActionEvent` with the result. The first implements Keystone's `ActionStartingEvent`, the second `ActionFinishedEvent`, so one listener hears every action of the suite. Finished events wait for the surrounding transaction to commit, and an Action that throws fires none.

The background steps of a CSV import or export announce themselves the same way: `ImportValidatingActionEvent` / `ImportValidatedActionEvent` while the file is checked, `ImportRowApplyingActionEvent` / `ImportRowAppliedActionEvent` around each confirmed row (as the person who confirmed it), `ExportBuildingActionEvent` / `ExportBuiltActionEvent` while the file is written, and `TransferFinishingActionEvent` / `TransferFinishedActionEvent` when the transfer completes, is cancelled or expires.

```php
use Illuminate\Support\Facades\Event;
use RefactorCircus\Keystone\Contracts\ActionFinishedEvent;
use RefactorCircus\Roster\Domains\User\Events\UserSuspendedActionEvent;

Event::listen(UserSuspendedActionEvent::class, fn ($event) => /* ... */);
Event::listen(ActionFinishedEvent::class, fn ($event) => /* every action */);
```

**Every model dispatches an event for each Eloquent hook**: `retrieved`, `creating`, `created`, `updating`, `updated`, `saving`, `saved`, `deleting`, `deleted` and `replicating`, plus `restoring`, `restored`, `trashed`, `forceDeleting` and `forceDeleted` on the soft-deleting `OrganizationModel` and `UserModel`. Each is a class named after the model and hook - `TeamModel` fires `RefactorCircus\Roster\Domains\Team\Events\TeamCreatingEvent` - holding the record (`$event->team`). They implement Keystone's `ModelLifecycleEvent` (`model()`, `hook()`), so `Event::listen(ModelLifecycleEvent::class, ...)` hears every model of the suite. As with Eloquent, a `creating`, `updating`, `saving` or `deleting` listener that returns `false` stops the write.

## Organizations and teams

An organization is the tenant. Users join it as members, and teams group members inside it. A user can belong to many organizations. Organizations and teams are identified by slug; team slugs are unique within their organization.

```php
use RefactorCircus\Roster\Domains\Organization\Actions\{AddMemberAction, CreateOrganizationAction};
use RefactorCircus\Roster\Domains\Organization\Data\{AddMemberData, CreateOrganizationData};
use RefactorCircus\Roster\Domains\Team\Actions\{AddTeamMemberAction, CreateTeamAction};
use RefactorCircus\Roster\Domains\Team\Data\{AddTeamMemberData, CreateTeamData};

$acme = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Acme'), owner: $user);
app(AddMemberAction::class)->execute($acme, new AddMemberData(user: $ada->getRouteKey()));

$ops = app(CreateTeamAction::class)->execute($acme, new CreateTeamData(name: 'Ops'));
app(AddTeamMemberAction::class)->execute($ops, new AddTeamMemberData(user: $ada)); // must be an org member
```

- An organization has at most one **owner**. The owner can't be removed, and `TransferOwnershipAction` hands ownership to another member. A user who owns a shared organization can't be deleted until they transfer it.
- An organization may have **no owner** (leave out `owner`), as organizations synced from an ERP usually start. Atrium flags it, and `TransferOwnershipAction` makes a member the owner. Personal organizations always have one.
- Removing a member also removes their team seats.
- **Personal organizations:** set `roster.organizations.personal` to `true` and every user created through Roster gets one. It is deleted with its owner and can't be deleted or transferred on its own.

### Current organization and team

```php
app(SwitchContextAction::class)->execute($user, new SwitchContextData(organization: 'acme', team: 'ops'));

$user->currentOrganization(); // falls back to the first organization joined
$user->currentTeam();         // null unless set and the user still holds a seat
```

Without the trait, use `app(RefactorCircus\Roster\Roster::class)->organization($user)` and `->team($user)`. Add the `roster.organization` middleware to routes that need a tenant; users who belong to no organization get a 403.

### Invitations

`CreateInvitationAction` invites an email address, optionally onto some teams, and emails a link. The address doesn't need an account yet.

- The link opens Roster's page at `/roster/invitation/{token}`. The page is signed, expires with the invitation, and sits behind `roster.invitations.middleware` (default `['web', 'auth']`). Guests go through your login or registration first.
- Only a signed-in user **whose email matches the invitation** can accept or decline it, so a forwarded link is useless to anyone else.
- After answering, the user is redirected to `roster.invitations.redirect`.
- To use your own page instead, set `roster.invitations.accept_url` (e.g. `https://app.test/join/{token}`) and call `AcceptInvitationAction` / `DeclineInvitationAction` from it.
- Only a hash of the token is stored.

### Domain auto-join

Give an organization domains and turn on `auto_join`. Users then join automatically **when they verify their email** (Laravel's `Verified` event), or when created already verified. Only user models implementing `MustVerifyEmail` ever count as verified, because an unverified address on `acme.com` proves nothing. A domain can belong to one organization only.

## Roles and permissions

Permissions are named abilities (`roster.users.update`, or your own `invoices.edit`). Roles bundle permissions and are assigned **globally**, **in an organization**, or **on a team**. Both live in the database and can be edited from Atrium, the API or MCP.

```php
use RefactorCircus\Roster\Domains\Permission\Actions\CreatePermissionAction;
use RefactorCircus\Roster\Domains\Permission\Data\CreatePermissionData;
use RefactorCircus\Roster\Domains\Role\Actions\{AssignRoleAction, CreateRoleAction};
use RefactorCircus\Roster\Domains\Role\Data\{AssignRoleData, CreateRoleData};
use RefactorCircus\Roster\Domains\Role\Enums\RoleScope;

app(CreatePermissionAction::class)->execute(new CreatePermissionData(name: 'invoices.edit'));

$billing = app(CreateRoleAction::class)->execute(new CreateRoleData(
    name: 'Billing',
    scope: RoleScope::Organization,     // Global | Organization | Team
    permissions: ['invoices.edit'],
));

app(AssignRoleAction::class)->execute($user, new AssignRoleData(role: $billing->id, organization: 'acme'));

$user->can('invoices.edit', $acme);  // true
$user->can('invoices.edit');         // checks the user's current organization/team
```

How a check resolves:

- **Scopes add up.** A user's permissions in an organization are their global roles plus their roles in that organization. On a team, they also include the team's roles.
- **Owners.** An organization's owner holds every permission inside it, except the global-only ones (`roster.users.*`, `roster.organizations.create`, `roster.organizations.sync`, `atrium.*`).
- **Super-admins** pass every Gate check, Roster's or your app's.
- **Gate integration.** `Gate::before` answers any ability that is a known permission. When the user lacks it, Roster returns `null` rather than `false`, so your own gates and policies still run.
- **Shared and custom roles.** A role without an organization is shared by every organization. With `organization`, it is that organization's own.
- **No escalation.** Actors can only grant permissions they hold in that scope. Only super-admins can assign or revoke super roles.
- **Remembered per request.** The permission names and each user's role assignments load once per request; Roster's own actions forget what they change (one user's answers for a change to that user, everything for a change to roles or permissions). Asking with a record (`$user->can('update', $membership)`) finds its organization or team once per request; eager-load `organization` / `team` on a list you check record by record and there is no lookup at all.

To keep the permission names and role assignments between requests too, set a cache lifetime (off by default):

```php
'permission_cache' => [
    'ttl' => 300,      // seconds; 0 keeps them per request only
    'store' => null,   // the cache store; null for the default
],
```

Roster's Actions and its role, assignment and permission models clear the cache. After writing those tables some other way, call `app(\RefactorCircus\Roster\Domains\Permission\Services\Permissions::class)->forgetCache()`.

Built-in roles (re-sync with `php artisan roster:sync-permissions` after upgrading; existing edits are kept):

| Role | Scope | Grants |
|---|---|---|
| `super-admin` | global | everything |
| `admin` | organization | every organization-scoped `roster.*` permission, including `roster.audit.view` |
| `member` | organization | `roster.organizations.view`, `roster.members.view`, `roster.teams.view`; given to every new member (`roster.roles.default_member`) |
| `lead` | team | `roster.teams.view`, `roster.teams.manage` |

### The first super-admin

```bash
php artisan roster:grant-super-admin admin@example.com
```

Alternatively, list emails in `roster.super_admins`. Listed emails only count once **verified**, on user models implementing `MustVerifyEmail`.

### Authorization of Roster's own surfaces

With `roster.authorization` on (the default), every API route, MCP tool and Atrium screen needs a signed-in user holding the matching `roster.*` permission. Guests get a 401 and other users a 403. Users can always view themselves and edit their own profile and current context.

If your app hasn't defined Atrium's `viewAtrium` gate, Roster defines it as the `atrium.view` permission.

### Policies

Each Roster model has a policy, registered with the Gate from `roster.policies`. Point a model at a class of your own to change who may do what:

```php
'policies' => [
    OrganizationModel::class => App\Policies\OrganizationPolicy::class,
    // ...
],
```

```php
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Domains\Organization\Policies\OrganizationPolicy as RosterOrganizationPolicy;

class OrganizationPolicy extends RosterOrganizationPolicy
{
    // Roster's API, MCP tools and Atrium screens pass the organization or team
    // the call is about ($subject), and the user it is about when everyone may
    // do it to themselves ($self). Your own checks pass the record.
    public function delete(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $user?->email === 'founder@example.com';
    }
}
```

**Roster's own checks ask them.** On every surface, permission `roster.{resource}.{verb}` asks the policy of the resource's model, by the camel-cased verb: `roster.organizations.update` asks `update()` on `OrganizationModel`'s policy, `roster.users.manage-status` asks `manageStatus()` on `UserModel`'s. The resources are `users` (`UserModel`, whatever your user model is), `organizations`, `members` (`MembershipModel`), `teams`, `invitations`, `roles` (which covers permissions), `sso` (`SsoConnectionModel`) and `scim` (`ScimTokenModel`). The other models' policies (profiles, domains, links, seats, assignments, permissions, impersonations, identities, SCIM records, transfers and their rows) answer your own checks: `$user->can('manage', $domain)`.

The bundled policies answer with Roster's permissions, so nothing changes until you swap one. Since checks now go through the Gate, your app's `Gate::before` callbacks apply to them too. Some things stay with Roster whatever a policy says: permissions blocked while impersonating are refused first, `roster.audit.view` has no model and is checked directly, and the organization-limited lists above still work out the caller's organizations from their roles. Remove a model's entry to check its resource by Roster's permissions alone.

**Organization admins.** A permission held only in some organizations (through an organization role, or by owning the organization) still opens the lists of organizations, roles, impersonations and transfers. Asked without an `organization`, those lists hold only what falls within the user's organizations: their organizations, the shared roles plus those organizations' own, and so on. On every surface: Atrium, the API and MCP. Asking for another organization is still refused. In Atrium, the matching navigation items appear for them too; Users and Permissions stay global, as does the users search.

## Auditing

Roster keeps no audit log of its own and records nothing itself. Install [`refactor-circus/keen`](https://github.com/Refactor-Circus/Keen), the audit log of the Refactor Circus suite, and Keen records every change Roster makes: users, profiles and status, organizations, members, teams, invitations, roles, permissions and assignments. Reads are not recorded. Without Keen there is no audit history anywhere in Roster.

```bash
composer require refactor-circus/keen
php artisan migrate
```

To keep Roster out of the audit log while Keen is installed, set `roster.audit.enabled` to `false`: Keen records nothing of Roster's, and Roster offers no history. `Roster::auditing()` says whether Roster audits right now.

While it does, Roster teaches Keen about its records through refactor-circus/keystone's `AuditHooks`:

- **Labels:** users by name or email, organizations, teams and roles by name, invitations by email, role assignments by role, transfers by type.
- **Field changes** include profile fields (`profile.status`), a role's permissions, an organization's domains, and the role, organization and team of an assignment.
- **Subject:** an entry about a role assignment or an impersonation is about the user; otherwise about the event's `user`, when it has one.
- **Scope:** the organization the change belongs to (a user's current organization for changes to the user alone), so Keen can be read per organization.
- **Context:** `impersonator` while someone is impersonating, `transfer` for a change made by a CSV import, and `scim_token` for one made through SCIM.
- **Surfaces:** SCIM routes are recorded as `scim`, and the signed invitation and impersonation pages as `web`.
- **Redaction:** the users table's password column, on top of Keen's own list.

### Reading it

While Roster audits:

- Each user, team and role page in Atrium, and the Activity tab of each organization, shows that record's history. They need `roster.audit.view` (included in the `admin` organization role) in that scope. Keen's own **Audit log** section reads everything.
- `GET /roster/history` and `list-roster-history-tool` serve Roster's history, to holders of the global `roster.audit.view`.
- If your app hasn't defined the `viewAuditLog` Gate ability that Keen and every package's history ask, Roster defines it as the global `roster.audit.view` permission. Define your own to take over.

Otherwise the pages show no history, the route answers 404, and the tool is not offered (to MCP clients or to Cortex).

Record your app's own events with Keen directly: `Keen::record('invoice.paid')->on($invoice)->in($organization)->save();`.

## Single sign-on

Each organization can sign its people in through its own identity provider: **OpenID Connect** (Okta, Auth0, Google Workspace, Keycloak …), **Microsoft Entra ID**, or **SAML 2.0** (ADFS, Okta, OneLogin …). SSO is built on Laravel Socialite, and its packages are optional. Install the ones you need:

```bash
composer require laravel/socialite firebase/php-jwt                      # OpenID Connect
composer require laravel/socialite socialiteproviders/microsoft-azure    # Microsoft Entra ID
composer require laravel/socialite socialiteproviders/saml2              # SAML 2.0
```

### Connecting an identity provider

Add a connection on the organization's **SSO** tab in Atrium, or with `CreateSsoConnectionAction`, the API or MCP:

| Protocol | Settings |
|---|---|
| `oidc` | `issuer` (https), `client_id`, `client_secret` |
| `azure` | `tenant` (tenant ID or verified domain), `client_id`, `client_secret` |
| `saml` | `metadata_url`, **or** `entity_id`, `sso_url` and `certificate` |

Register the connection's **callback URL** at the provider. It's shown in Atrium and returned by the API: `/roster/sso/{connection}/callback`, the SAML ACS. For SAML, give the provider the service provider metadata at `/roster/sso/{connection}/metadata`. Secrets are encrypted at rest and never returned by any surface.

### Signing in

Send people to `/roster/sso?email=ada@acme.com`, which finds their organization's connection by email domain, or straight to `/roster/sso/{connection}`. After the provider confirms them:

1. **A returning user** is matched by their identity at the provider (its subject), not by email.
2. **An existing account with the same email** is linked automatically, but **only if the organization owns that email's domain**. Anyone else can link from their account while signed in (`POST /roster/sso/{connection}/link`).
3. **Otherwise, a new account is created** (`jit`, on by default), again only for the organization's own domains, with the email marked verified.

The user becomes a member of the organization. Suspended or deactivated users are refused. With Keen installed, every sign-in and refusal is in the audit log (`sso_login.succeeded` / `sso_login.failed`).

**Security notes**

- An identity provider is only trusted for its organization's verified domains, so a provider can't vouch for `ceo@someone-else.com`.
- **OIDC** id_tokens are verified against the provider's published keys (signature, issuer, audience, expiry, nonce).
- **SAML** assertions are verified by LightSAML.
- **Entra ID** connections need a specific tenant (`common`, `organizations` and `consumers` are refused), and use the account's `userPrincipalName`. Roster never uses the `mail` attribute, which tenant admins can set to any address.

### Requiring SSO

Turn on `enforced` to make SSO mandatory for an organization's domains. Roster has no password login of its own; add the rule to yours:

```php
use RefactorCircus\Roster\Domains\Sso\Support\NotSsoEnforced;

$request->validate(['email' => ['required', 'email', new NotSsoEnforced]]);
```

It fails with a link to the organization's SSO sign-in. Super-admins are never forced, so you can't lock yourself out. `Roster::ssoRequiredFor($email)` gives the same answer in code.

## Syncing organizations from external systems

When your organizations live in another system of record, such as an ERP or a CRM, Roster can create and update them from those records. It remembers each organization's id and account number in every system it comes from.

```php
use RefactorCircus\Roster\Domains\Organization\Actions\SyncOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\SyncOrganizationData;

$result = app(SyncOrganizationAction::class)->execute(SyncOrganizationData::from([
    'source' => 'erp',              // which system, lower case
    'external_id' => 'C-100',       // its id there
    'account_number' => 'A-42',
    'name' => 'Initech',
    'domains' => ['initech.test'],
]));

$result->outcome;                   // 'created', 'updated' or 'unchanged'
$result->organization->links;       // [OrganizationLink{source: erp, external_id: C-100, account_number: A-42}]
```

- **Matching** is by `source` + `external_id`. An unknown record creates an organization. To link an existing organization on its first sync, pass `organization` (its slug) instead.
- **Only the fields you send are written:** `name`, `slug`, `domains`, `auto_join` and `account_number`. Anything left out stays as it is. Local edits are allowed, and the next sync overwrites them.
- **Owners.** A new organization has no owner unless the record names one (`owner`, a user key). An owner is only applied to an organization that doesn't have one yet; sync never replaces an existing owner.
- **Several systems:** an organization can be linked to one record per source, for example `erp` and `crm`. Manage links by hand with `LinkOrganizationAction` / `UnlinkOrganizationAction`, or on the organization's Settings tab in Atrium.
- **Finding them:** `ListOrganizationsAction` and `GET /roster/organizations` take `source`, `external_id` and `account_number` filters.
- **Exporting:** the `export_organizations` CSV uses the same columns as the import, so you can export, edit and re-import. Atrium's Organizations page links to Imports & exports.
- **In bulk:** `SyncOrganizationsAction` (`POST /roster/organizations/sync`) takes up to `roster.organizations.sync_batch` (500) records. Each record succeeds or fails on its own, and the response lists `created` / `updated` / `unchanged` / `error` (with messages) per record, in order. For files, use the `import_organizations` CSV import.
- **Permission:** the sync Actions and the CSV import need the global `roster.organizations.sync` permission. Give an integration user just that, plus `roster.organizations.view` if it needs to look organizations up. Linking and unlinking by hand need `roster.organizations.update` in the organization.
- **Audit:** with Keen installed, each sync records `organization.synced` with the source, id and outcome, next to the usual `organization.created` / `organization.updated` entries. A batch also records `organizations.synced` with its counts.

## SCIM provisioning

Each organization gets a SCIM 2.0 endpoint, so its identity provider (Okta, Microsoft Entra ID, OneLogin, JumpCloud …) keeps its members and teams in sync:

| Okta / Entra setting | Value |
|---|---|
| SCIM base URL (Tenant URL) | `https://your-app.test/scim/v2/{organization-slug}` |
| Authentication | Bearer token, from the organization's **SCIM** tab in Atrium (or `POST /roster/organizations/{organization}/scim-tokens`) |
| Unique identifier | `userName` (email) |

Tokens are shown **once**, stored only as a hash, can expire, can be revoked, and record when they were last used. A token only works for its own organization.

**What the provider can do**

- **Users are the organization's members.** Creating one makes the person a member, linking their existing account when the organization owns the email's domain, or creating a new account. Emails on other domains are refused, so a SCIM token can't claim accounts it doesn't own.
- **Groups are the organization's teams.** The provider can create and rename them and sync their members.
- **Deprovisioning** (`active: false`, or `DELETE`) removes the membership, with its team seats and roles. The account is deactivated only if SCIM created it and it belongs to no other organization. Accounts are never deleted. Setting `active: true` restores the membership.
- **The owner** can't be deprovisioned; that's a 409 until ownership is transferred.

**Supported (RFC 7643/7644)**

- `/Users` and `/Groups` with GET, POST, PUT, PATCH and DELETE, plus `/Bulk`, `/ServiceProviderConfig`, `/ResourceTypes` and `/Schemas`.
- **Filters:** `eq`, `co` and `sw`, joined by `and`, on `userName`, `externalId`, `emails.value`, `id`, `displayName` and `members.value`.
- **PATCH:** `add`, `replace` and `remove`, including Entra's capitalised ops and string booleans, and Okta's `members[value eq "…"]`.
- **Paging:** `startIndex` and `count`.
- **ETags:** `If-Match` returns 412 on a stale version; `If-None-Match` returns 304.
- **Bulk:** `bulkId` references and `failOnErrors`, up to `roster.scim.bulk.max_operations` operations.

**Linking to SSO.** When you issue a token, you can name one of the organization's SSO connections. The provider's `externalId` then becomes that connection's SSO subject, so a provisioned user's first SSO sign-in lands on their account. Map Okta's user ID or Entra's objectId to `externalId`.

**Audit.** Every SCIM change runs through Roster's Actions, so with Keen installed it is recorded with surface `scim` and the name of the token that made it.

## Impersonation

Holders of `roster.users.impersonate` can temporarily act as another user to see what they see. Nobody gets it by default, so grant it deliberately: globally to impersonate anyone, or in an organization to impersonate its members.

```php
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;

$started = app(StartImpersonationAction::class)->execute($user, new StartImpersonationData(reason: 'Ticket #4521'), actor: $admin);

return redirect($started->url); // a one-time link, valid for a few minutes
```

In Atrium, use the **Impersonate** card on a user's page. Over the API and MCP, the link is returned once for the operator to open.

**Safety rails**

- **The link only works in the impersonator's own browser** (signed in as them), works once, and expires after `link_minutes` (5). Only its hash is stored.
- **Refused targets:** yourself, inactive users, super-admins (unless you are one), and anyone holding a permission you lack. You can't impersonate while already impersonating.
- **A reason is required**, and the session ends on its own after `ttl_minutes` (30): on the impersonator's next request, or, if they never come back, when `php artisan roster:end-expired-impersonations` runs. Schedule it (every five minutes, say) so every expired session and unused link is ended on the record.
- **Blocked while impersonating:** the abilities in `roster.impersonation.blocked` are refused, even for super-admins. This works through Roster's checks and Laravel's Gate, so your own abilities can be listed too (wildcards like `billing.*`). The impersonated account's email and password can't be changed.
- **Ending it:** a global holder can end anyone's impersonation (Atrium → Impersonations). The browser switches back on its next request.

**The banner.** Show it in your own layout:

```blade
@include('roster::impersonation-banner')
```

It names who you're acting as and who you really are, shows the time left, and has a "Return to my account" button. It is Atrium's banner in standalone mode, styled inline, so it needs nothing from Atrium's stylesheet on your own pages. Pass `['bannerClass' => '...']` to add classes. Roster's Atrium pages already include it. (It was the `<x-roster::impersonation-banner />` component before Roster dropped its component namespace.)

**Audit.** With Keen installed, everything done while impersonating is recorded as the impersonated user, with `context.impersonator` naming the real person. Starting, entering and ending each get their own entry.

The `roster.impersonation` middleware, which ends expired or revoked sessions, is added to the `web` group automatically. Set `roster.impersonation.middleware_group` to null to add it yourself.

## CSV import and export

Bulk-add members, users and teams from a CSV, and export members, users or organizations. Imports and exports run in the background as [`refactor-circus/impex`](https://github.com/Refactor-Circus/Impex) flows, so Impex is needed for this feature:

```bash
composer require refactor-circus/impex
php artisan vendor:publish --tag="impex-migrations"
php artisan migrate
```

Run a queue worker, and schedule Laravel's scheduler (Impex's `impex:tick` expires unconfirmed imports) and `roster:prune-transfers`. Without Impex, the transfer Actions throw a `TransfersUnavailableException` that names the missing package, and the Atrium page shows the same setup note.

**Imports are previewed, then confirmed.** Uploading checks every row against the current data and stores a preview. Nothing changes until someone confirms it:

| Row outcome | Meaning |
|---|---|
| `create` | A new account (or team) is created |
| `link` | An existing account on the organization's domains joins |
| `invite` | The email is outside the organization's domains, so an invitation is sent |
| `update` | Adds teams, a role or team members; nothing is ever removed |
| `skip` | Already as the row describes |
| `error` | The reason is shown, and the row is left out |

The preview's rows come back a page at a time (`rows_page`, 100 per page) from `GET /roster/transfers/{transfer}` and `show-transfer-tool`, and are kept in `roster_transfer_rows`. Confirming applies the rows one by one, as the person who confirmed. Their permissions are checked again, and each row is checked again against the data at that moment. Errors are reported per row; the rest still apply. A retried row is never applied twice. An import not confirmed within `roster.transfers.confirm_within_hours` (24) expires.

| Type | Columns (first row is the header) | Permission |
|---|---|---|
| `import_members` | `email` required; `name`, `display_name`, `teams`, `role` | `roster.members.manage` in the organization; `invite` rows also need `roster.invitations.manage` |
| `import_users` | `email` required; `name`, `display_name` | `roster.users.create` |
| `import_teams` | `name` required; `slug`, `members` (emails of existing members) | `roster.teams.manage` in the organization |
| `import_organizations` | `source`, `external_id` required; `name` (required for new ones), `account_number`, `slug`, `domains`, `owner` (an email). Blank cells leave a field as it is | `roster.organizations.sync` |
| `export_members` | email, name, display name, status, teams, roles, owner, source, joined | `roster.members.view` in the organization |
| `export_users` | id, email, name, display name, status, created | `roster.users.view` |
| `export_organizations` | the `import_organizations` columns, one row per external record (unlinked organizations get one row with no source), so an edited file imports straight back; optional `filters.external_source` keeps one system's records | `roster.organizations.view` |

**Templates.** Every import type has a CSV template: the header row, plus commented example rows. Rows whose first cell starts with `#` are ignored, so an untouched template imports nothing; it's refused as having no rows. Download them in Atrium (the template picker on Imports & exports), from `GET /roster/imports/templates/{type}`, or with `show-import-template-tool`. Any signed-in user may download them, since they hold no data. To change them (for example to match your own wording or examples), publish them:

```bash
php artisan vendor:publish --tag="roster-import-templates"
```

The published copies in `resources/roster/import-templates/{type}.csv` are served instead of Roster's own.

Separate lists (`teams`, `members`, `domains`) with `;`, `,` or `|`. Teams are matched by slug or name, and roles by slug. You can only assign roles whose permissions you hold.

```php
use RefactorCircus\Roster\Domains\Transfer\Actions\ConfirmImportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartExportAction;
use RefactorCircus\Roster\Domains\Transfer\Actions\StartImportAction;
use RefactorCircus\Roster\Domains\Transfer\Data\{StartExportData, StartImportData};
use RefactorCircus\Roster\Domains\Transfer\Enums\TransferType;

$import = app(StartImportAction::class)->execute(new StartImportData(
    type: TransferType::ImportMembers,
    organization: 'acme',
    file: $request->file('csv'),          // or content: $csvText
), $request->user());

$import->rows();                            // [['line' => 2, 'action' => 'invite', 'reasons' => [...]], ...]

app(ConfirmImportAction::class)->execute($import, $request->user());

$export = app(StartExportAction::class)->execute(new StartExportData(type: TransferType::ExportMembers, organization: 'acme'), $request->user());
```

**Safety**

- **No passwords.** A file with a `password` column is refused. New accounts get a random password; people reset it or sign in with SSO.
- **Accounts outside the organization's domains are never claimed.** Those rows become invitations, the same rule SCIM follows.
- **Limits:** `max_bytes` (5 MB) and `max_rows` (10,000). UTF-8 with or without a BOM is accepted.
- **Formula injection:** exported cells starting with `=`, `+`, `-`, `@`, a tab or a carriage return get a leading `'`, so spreadsheets show them as text.
- **Files** live on `roster.transfers.disk` (keep it private) under `roster/transfers/{id}`. They are only served through Roster's authorized download route, to whoever started the transfer or holds its permission in scope. MCP gets a signed link valid for 15 minutes. `roster:prune-transfers` deletes files older than `retention_days` (7).

**Audit.** Every row is applied as the person who confirmed, so with Keen installed it is recorded as theirs, with surface `import` and `context.transfer` naming the import. Starting, confirming, cancelling and finishing each have their own entry.

## Deleting and restoring

Deleting a user or an organization is recoverable. It goes to **Deleted** (Atrium's lists have a Show: Current / Deleted filter), and can be restored with everything it had until it's deleted permanently.

- **Users** need Laravel's `SoftDeletes` on the user model. Roster's bundled `RefactorCircus\Roster\Domains\User\Models\UserModel` has it; add it to your own model and a `deleted_at` column (`$table->softDeletes()`):

  ```php
  use Illuminate\Database\Eloquent\SoftDeletes;

  class User extends Authenticatable
  {
      use HasRoster, SoftDeletes;
  }
  ```

  Laravel then hides deleted users everywhere, sign-in included. Their profile, memberships, team seats, roles and personal organization are kept for a restore. A model without the trait is deleted permanently, as before, and the warning says so.
- **Organizations** always soft-delete. A deleted organization is switched off: its pages and API 404, its members lose access, and its SSO, SCIM and domain auto-join stop. **Its slug and domains stay reserved**, so a restore always works.
- **Restore** with `RestoreUserAction` / `RestoreOrganizationAction`, `POST /roster/users/{user}/restore` / `POST /roster/organizations/{organization}/restore`, the MCP restore tools, or the Restore buttons in Atrium. It needs the same permission as deleting.
- **Delete permanently** with `PurgeUserAction` / `PurgeOrganizationAction`, `DELETE …/purge`, the MCP purge tools, or the deleted record's Danger zone in Atrium. Only deleted records can be purged, and it needs `roster.users.purge` / `roster.organizations.purge`.
- **Organizations are purged in the background**, since a large one takes all its teams, members, invitations, roles and connections with it: the API, MCP and Atrium check the request at once (a refusal answers right away), then queue a `PurgeOrganizationJob` and answer `202 Accepted`, so the organization disappears shortly after. Call `PurgeOrganizationAction::queue($organization)` to do the same, or `execute()` to purge in place. Run a queue worker. An organization restored before the job runs is kept.
- **Automatically:** schedule `php artisan roster:purge-deleted` to permanently delete records deleted more than `roster.deletes.retention_days` days ago (organizations are queued, one job each). The default, `null`, keeps deleted records forever; `--days=N` overrides it for one run.

## Approving new users

New accounts can wait for an approver instead of becoming active straight away. They then have the status `pending` ("Awaiting approval"). Who starts pending depends on how the account was made:

| Created by | Starting status |
|---|---|
| The app's own registration (Laravel's `Registered` event, which Breeze, Fortify and Jetstream fire) | `roster.users.registration_status`: `active` (default) or `pending` |
| An admin: `CreateUserAction`, Atrium's create form, the API or MCP | Their choice, through the `status` input (`active` by default) |
| An organization's SSO (just-in-time accounts) or SCIM | That organization's `provisioned_status` setting (`active` by default), on its Settings tab |

CSV imports create active users.

- **Approving and rejecting:**
  - `ApproveUserAction` makes a pending account active.
  - `RejectUserAction` deactivates it with an optional `reason`. The account is kept and can be reactivated later, and the email stays taken.
  - Both need the global `roster.users.approve` permission. Organization admins don't get it, because accepting an account is a site decision.
  - In Atrium, a pending user's page shows Approve and Reject buttons, and the Users list filters by "Awaiting approval".
- **While pending:**
  - `roster.active` answers 403 with "Your account is awaiting approval".
  - SSO sign-in is refused with the same message. The account, its membership and its SSO link are still kept, so it's ready once approved.
  - Reactivating a pending account is refused; approve it instead.
- **Emails,** each switchable in `roster.users.approvals`:
  - `notify_approvers` tells everyone with `roster.users.approve` (super-admins included) when someone is waiting.
  - `notify_user` tells people when they're approved or rejected.

## Blocking inactive users

```php
Route::middleware(['auth', 'roster.active'])->group(...);
```

Suspended and deactivated users get a 403, and so do users awaiting approval (with their own message). A user with no profile row is active.

## HTTP API and MCP

> **Security:** both are **off by default**. Every route and tool checks the caller's Roster permissions, but that needs an authenticated user, so add authentication middleware before enabling them.

```php
'routes' => ['enabled' => true, 'prefix' => 'roster', 'middleware' => ['api', 'auth:sanctum']],
'mcp' => ['web' => ['enabled' => true, 'route' => 'mcp/roster', 'middleware' => ['api', 'auth:sanctum']]],
```

| Method | URI | Name |
|---|---|---|
| GET | `/roster/users` | `roster.users.index` |
| POST | `/roster/users` | `roster.users.store` |
| GET | `/roster/users/{user}` | `roster.users.show` |
| PATCH | `/roster/users/{user}` | `roster.users.update` |
| DELETE | `/roster/users/{user}` | `roster.users.destroy` |
| PATCH | `/roster/users/{user}/profile` | `roster.users.profile.update` |
| POST | `/roster/users/{user}/suspend` | `roster.users.suspend` |
| POST | `/roster/users/{user}/deactivate` | `roster.users.deactivate` |
| POST | `/roster/users/{user}/reactivate` | `roster.users.reactivate` |
| POST | `/roster/users/{user}/approve` | `roster.users.approve` |
| POST | `/roster/users/{user}/restore` | `roster.users.restore` |
| DELETE | `/roster/users/{user}/purge` | `roster.users.purge` (deleted users only) |
| POST | `/roster/users/{user}/reject` | `roster.users.reject` |
| PUT | `/roster/users/{user}/context` | `roster.users.context.update` |
| POST | `/roster/users/{user}/domain-join` | `roster.users.domain-join` |
| GET, POST | `/roster/organizations` | `roster.organizations.index`, `.store` |
| GET, PATCH, DELETE | `/roster/organizations/{organization}` | `roster.organizations.show`, `.update`, `.destroy` |
| POST | `/roster/organizations/{organization}/transfer` | `roster.organizations.transfer` |
| POST | `/roster/organizations/{organization}/restore` | `roster.organizations.restore` |
| DELETE | `/roster/organizations/{organization}/purge` | `roster.organizations.purge` (deleted organizations only; 202, purged by a queued job) |
| PUT | `/roster/organizations/external/{source}/{externalId}` | `roster.organizations.sync` (upsert; 201 when created) |
| POST | `/roster/organizations/sync` | `roster.organizations.sync-many` (`{records: [...]}`) |
| PUT, DELETE | `/roster/organizations/{organization}/links/{source}` | `roster.organizations.links.update`, `.destroy` |
| GET, POST | `/roster/organizations/{organization}/members` | `roster.organizations.members.index`, `.store` |
| DELETE | `/roster/organizations/{organization}/members/{user}` | `roster.organizations.members.destroy` |
| GET, POST | `/roster/organizations/{organization}/teams` | `roster.organizations.teams.index`, `.store` |
| GET, PATCH, DELETE | `/roster/organizations/{organization}/teams/{team}` | `roster.organizations.teams.show`, `.update`, `.destroy` |
| POST | `/roster/organizations/{organization}/teams/{team}/members` | `roster.organizations.teams.members.store` |
| DELETE | `/roster/organizations/{organization}/teams/{team}/members/{user}` | `roster.organizations.teams.members.destroy` |
| GET, POST | `/roster/organizations/{organization}/invitations` | `roster.organizations.invitations.index`, `.store` |
| DELETE | `/roster/organizations/{organization}/invitations/{invitation}` | `roster.organizations.invitations.revoke` |
| POST | `/roster/invitations/{token}/accept` | `roster.invitations.accept` (as the authenticated user) |
| POST | `/roster/invitations/{token}/decline` | `roster.invitations.decline` (as the authenticated user) |
| GET, POST | `/roster/permissions` | `roster.permissions.index`, `.store` |
| PATCH, DELETE | `/roster/permissions/{permission}` | `roster.permissions.update`, `.destroy` |
| GET, POST | `/roster/roles` | `roster.roles.index`, `.store` |
| GET, PATCH, DELETE | `/roster/roles/{role}` | `roster.roles.show`, `.update`, `.destroy` |
| GET, POST | `/roster/users/{user}/roles` | `roster.users.roles.index`, `.store` |
| DELETE | `/roster/users/{user}/roles/{assignment}` | `roster.users.roles.destroy` |
| GET | `/roster/users/{user}/permissions` | `roster.users.permissions` |
| GET, POST | `/roster/organizations/{organization}/sso-connections` | `roster.organizations.sso-connections.index`, `.store` |
| GET, PATCH, DELETE | `/roster/sso-connections/{connection}` | `roster.sso-connections.show`, `.update`, `.destroy` |
| GET | `/roster/users/{user}/sso-identities` | `roster.users.sso-identities.index` |
| DELETE | `/roster/sso-identities/{identity}` | `roster.sso-identities.destroy` |
| GET, POST | `/roster/organizations/{organization}/scim-tokens` | `roster.organizations.scim-tokens.index`, `.store` |
| DELETE | `/roster/scim-tokens/{token}` | `roster.scim-tokens.destroy` |
| POST | `/roster/users/{user}/impersonate` | `roster.users.impersonate` |
| GET | `/roster/impersonations` | `roster.impersonations.index` |
| DELETE | `/roster/impersonations/{impersonation}` | `roster.impersonations.destroy` |
| GET | `/roster/history` | `roster.history.index` (the shared refactor-circus/keen history; 404 until it is installed) |
| POST | `/roster/imports` | `roster.imports.store` (multipart `file`, or `content`) |
| GET | `/roster/imports/templates/{type}` | `roster.imports.templates.show` (CSV template; any signed-in user) |
| POST | `/roster/imports/{transfer}/confirm` | `roster.imports.confirm` |
| POST | `/roster/exports` | `roster.exports.store` |
| GET | `/roster/transfers` | `roster.transfers.index` |
| GET, DELETE | `/roster/transfers/{transfer}` | `roster.transfers.show`, `.destroy` (cancel) |
| GET | `/roster/transfers/{transfer}/download` | `roster.transfers.download` |
| GET | `/roster/transfer-files/{transfer}` | `roster.transfers.file` (signed links for MCP; always on) |

`GET .../members` takes `team` (a team's slug) to list only that team's members. A team's own response (`GET .../teams/{team}`) carries at most its first 100 members, with the full `members_count`; page through the rest with `members?team=`.

`{user}` is the user model's route key; `{organization}` and `{team}` are slugs; `{role}`, `{assignment}`, `{invitation}` and `{entry}` are ids; `{permission}` is the permission name. Responses use a `data` envelope. The MCP server offers one tool per Action, and each tool returns the same payload as the HTTP API as structured content: the record under `data`, a list's page under `meta` (`current_page`, `last_page`, `per_page`, `total`), and extras such as a sync's `outcome` beside them. Every tool that answers this way declares an output schema, so an agent knows each field, its type and its allowed values before calling it. Deletes and the import template answer with text.

MCP tools (all behind Laravel MCP's tool search):

| Area | Tools |
|---|---|
| Users | `list-users-tool`, `show-user-tool`, `create-user-tool`, `update-user-tool`, `delete-user-tool`, `update-profile-tool`, `suspend-user-tool`, `deactivate-user-tool`, `reactivate-user-tool`, `approve-user-tool`, `reject-user-tool`, `restore-user-tool`, `purge-user-tool`, `switch-context-tool`, `join-by-domain-tool` |
| Organizations | `list-organizations-tool`, `show-organization-tool`, `create-organization-tool`, `update-organization-tool`, `delete-organization-tool`, `restore-organization-tool`, `purge-organization-tool`, `transfer-ownership-tool`, `list-members-tool`, `add-member-tool`, `remove-member-tool`, `sync-organization-tool`, `sync-organizations-tool`, `link-organization-tool`, `unlink-organization-tool` |
| Teams | `list-teams-tool`, `show-team-tool`, `create-team-tool`, `update-team-tool`, `delete-team-tool`, `add-team-member-tool`, `remove-team-member-tool` |
| Invitations | `list-invitations-tool`, `create-invitation-tool`, `revoke-invitation-tool`, `accept-invitation-tool`, `decline-invitation-tool` |
| Single sign-on | `list-sso-connections-tool`, `show-sso-connection-tool`, `create-sso-connection-tool`, `update-sso-connection-tool`, `delete-sso-connection-tool`, `list-sso-identities-tool`, `unlink-sso-identity-tool` |
| SCIM | `list-scim-tokens-tool`, `create-scim-token-tool`, `revoke-scim-token-tool` |
| Impersonation | `start-impersonation-tool`, `list-impersonations-tool`, `stop-impersonation-tool` |
| History | `list-roster-history-tool` (reads refactor-circus/keen) |
| CSV import and export | `show-import-template-tool`, `start-import-tool`, `confirm-import-tool`, `start-export-tool`, `list-transfers-tool`, `show-transfer-tool`, `cancel-transfer-tool` |
| Roles | `list-permissions-tool`, `create-permission-tool`, `update-permission-tool`, `delete-permission-tool`, `list-roles-tool`, `show-role-tool`, `create-role-tool`, `update-role-tool`, `delete-role-tool`, `list-role-assignments-tool`, `assign-role-tool`, `revoke-role-tool`, `list-user-permissions-tool` |

### Rate limiting

The default API and MCP middleware include `throttle:roster`: 120 requests per minute per signed-in user, or per IP for guests. Change it with `roster.rate_limit.per_minute` (null turns it off). Missing records answer `404 {"message": "Not found."}` without naming internal classes.

## Cortex

When [`refactor-circus/cortex`](https://github.com/Refactor-Circus/cortex) is installed, Roster registers its MCP server and every tool with Cortex. Nothing needs registering in your app. Cortex agents can then manage users, organizations and roles, and you can publish new versions of the server instructions and tool descriptions in Cortex.

- Agents act as the **signed-in user**, so Roster's permission checks apply. A run with no signed-in user is refused.
- Limit what agents see with `roster.cortex.tools` (a list of tool names), or turn the integration off with `roster.cortex.enabled`.

### MCP redirect domains

MCP clients sign people in through OAuth, and laravel/mcp only accepts redirect URIs on allowed origins. With Cortex installed (and `cortex.redirect_domains.enabled` left on), Roster lets each organization and user keep their own instead of everyone sharing `MCP_REDIRECT_DOMAINS`:

- An organization's **MCP domains** tab, for those holding `roster.organizations.update` in it.
- An **MCP redirect domains** card on a user's page, listed to those who may view the user and editable with `roster.users.update`.

Cortex stores the domains with the organization or user as owner and accepts them when a client registers. Registration is anonymous, so a domain added for one organization is allowed for every client; the owner decides who manages it. Without Cortex neither appears.

## Atrium dashboard

Roster registers itself with Atrium automatically. It adds:

- a **Users** section: list, filter, create, edit account and profile, suspend/deactivate/reactivate, delete, memberships and context switching
- an **Organizations** section: members, ownership, teams, invitations, settings (domains, auto-join) and external records (link, unlink; filter the list by source, external id or account number)
- **Roles** and **Permissions** sections, a Roles card on each user, and a Roles tab on each organization
- **Impersonations** (active and history, end any) and an Impersonate card on each user
- each user, team and role's history, and an Activity tab on each organization, read from refactor-circus/keen when it is installed
- an **SSO** tab on each organization (connections with their callback and metadata URLs) and SSO identities on each user
- a **SCIM** tab on each organization (base URL, issue tokens that are shown once, revoke)
- **Imports & exports**: upload a CSV, review the preview, confirm or cancel, follow progress, and download exports, also reached from each organization and from Users
- "Users by status" and "Organizations" widgets
- navigation, widgets and search hidden from users without the matching permission
- on every page, only the controls the viewer may use: each form, button and organization tab asks the same permission, in the same organization or team, as the action behind it (Impersonate needs `roster.users.impersonate`, the Roles card `roster.roles.view`, assigning `roster.roles.assign`, each import or export type its own permission, and so on). Opening a tab you may not see is refused. Published views can do the same with `@rosterCan('roster.users.update', $organization, $self) ... @endrosterCan`
- search over users and organizations, matching from the start of a name, email or slug (the Users and Organizations screens' filters match anywhere)

Access follows Atrium's `viewAtrium` gate. To hide it, add `'roster'` to `atrium.disabled`. Roster can also be switched by feature flag. `roster.atrium.features` lists the features that must all be on: while any is off, Roster's navigation, widgets and search disappear and its pages answer 404. Atrium asks its feature resolver, so Pennant (through `refactor-circus/pennantplus`) or any other flag system decides.

By default it lists `RefactorCircus\Roster\Atrium\Features\RosterSupportFeature`, a PennantPlus feature that is on until its global value is set. Its `SupportFeature` suffix matches PennantPlus's `gate.global_only` pattern, so only the global value counts and who sees which page stays with Roster's permissions. Turn Roster off for everyone with `Feature::for(null)->deactivate(RosterSupportFeature::class)` or from the Feature flags page. Without `refactor-circus/pennantplus` the class is skipped and nothing is checked.

To change the default, point the config at a subclass:

```php
use RefactorCircus\Roster\Atrium\Features\RosterSupportFeature;

class RosterFeature extends RosterSupportFeature
{
    protected function default(): bool
    {
        return false; // off until switched on
    }
}

// config/roster.php
'atrium' => ['features' => [App\Features\RosterFeature::class]],
```

A class name that no longer ends in `SupportFeature` also follows per-user values.

## Trying it locally

The workbench is a demo app with Atrium and seeded data:

```bash
composer serve
```

It signs you in as the super-admin `admin@example.com` (password `password`) and opens `/atrium`. You'll find organizations Acme and Globex with teams, members and a pending invitation, plus a custom `invoices.edit` permission and a Billing role. The JSON API is mounted at `/roster`. To run the MCP server over stdio:

```bash
php vendor/bin/testbench mcp:start roster
```

## Testing

```bash
composer test          # static analysis, formatting, type coverage, Pest
composer test:browser  # Atrium in a real browser (run `npm install` first for Playwright)
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Roster! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Refactor Circus](https://github.com/Refactor-Circus)
- [All Contributors](../../contributors)

## License

Roster is open-sourced software licensed under the [MIT license](LICENSE.md).
