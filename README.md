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

Headless, Action-first user management for Laravel. Every operation is a single Action class, reachable from PHP, a JSON API and an MCP server. Roster ships no admin screens: its UI is optional, and comes from [Atrium v2](https://github.com/Refactor-Circus/Atrium), whose Nuxt layer calls the JSON API through the committed [OpenAPI document and TypeScript client](#openapi-document-and-typescript-client). Any other frontend can use the same client.

- User create, update, delete and listing on **your own** user model
- Profiles (display name, avatar, timezone, locale, bio, meta) in a side table, so your `users` table is never altered
- An active / suspended / deactivated lifecycle with a `roster.active` middleware
- Organizations with teams, members, email invitations, optional domain auto-join, and a per-user current organization/team
- Roles and permissions at global, organization and team scope, resolved through Laravel's Gate (`$user->can()`, `@can`)
- Every change recorded in the suite-wide audit log of [`refactor-circus/keen`](https://github.com/Refactor-Circus/Keen), when installed
- Impersonation through one-time links, with time limits, a banner, blocked abilities and a full audit trail (with refactor-circus/keen)
- A JSON API (off by default) with an [OpenAPI 3.1 document](#openapi-document-and-typescript-client), a typed TypeScript client, and an MCP server whose tools answer with the same payloads
- Extension seams for other packages: single sign-on and SCIM provisioning per organization come from [`refactor-circus/guardian`](https://github.com/Refactor-Circus/Guardian) (see [SSO and SCIM](#sso-and-scim-refactor-circusguardian))

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

The views (the plain invitation page and the impersonation banner) and translations can be published with `roster-views` and `roster-lang`.

Roster has no admin screens of its own. For a UI, use Atrium v2, which talks to Roster's [HTTP API](#http-api-and-mcp) and brings its own access gate; turn the API on first (it is off by default).

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
$user->rosterStatus();    // a case of the roster.statuses.users enum: UserStatus::Active | Pending | Suspended | Deactivated by default
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

**Untrusted input validates itself.** `XData::from($input)` checks the input against the Data's `rules()` (throwing a `ValidationException`) and fills each typed field from its snake_case key, casting enums, numbers and booleans. The signed pages build their Data this way. The API and MCP requests validate once, with the Data's rules among their own (`XData::rules()`), and build with `XData::fromValidated($request->validated())`, so every error comes back in one response; every surface validates with the same rules. `new XData(...)` is for values your own code vouches for, and is not validated.

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
- An organization may have **no owner** (leave out `owner`), as organizations synced from an ERP usually start. Its `owner` is then `null`, and `TransferOwnershipAction` makes a member the owner. Personal organizations always have one.
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

- The link opens Roster's page at `/roster/invitation/{token}`. The page is signed, expires with the invitation, and sits behind `roster.invitations.middleware` (default `['web', 'auth']`). Guests go through your login or registration first. It is a plain HTML page with accept and decline forms, rendered through the `RefactorCircus\Roster\Domains\Invitation\Contracts\InvitationPage` contract (bound to `PlainInvitationPage`); bind your own implementation to render it differently.
- Only a signed-in user **whose email matches the invitation** can accept or decline it, so a forwarded link is useless to anyone else.
- After answering, the user is redirected to `roster.invitations.redirect`.
- To use your own page instead (Atrium v2's, say), set `roster.invitations.accept_url` (e.g. `https://app.test/join/{token}`). The page can show the invitation to a guest through the [public invitation lookup](#public-invitation-lookup), then accept or decline it once the user is signed in (`POST /roster/invitations/{token}/accept`, or `AcceptInvitationAction` / `DeclineInvitationAction` in your own code).
- Only a hash of the token is stored.

### Domain auto-join

Give an organization domains and turn on `auto_join`. Users then join automatically **when they verify their email** (Laravel's `Verified` event), or when created already verified. Only user models implementing `MustVerifyEmail` ever count as verified, because an unverified address on `acme.com` proves nothing. A domain can belong to one organization only.

## Roles and permissions

Permissions are named abilities (`roster.users.update`, or your own `invoices.edit`). Roles bundle permissions and are assigned **globally**, **in an organization**, or **on a team**. Both live in the database and can be edited from the API or MCP.

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
- **Owners.** An organization's owner holds every permission inside it, except the global-only ones (`roster.users.*`, `roster.organizations.create`, `roster.organizations.sync`).
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

With `roster.authorization` on (the default), every API route and MCP tool needs a signed-in user holding the matching `roster.*` permission. Guests get a 401 and other users a 403. Users can always view themselves and edit their own profile and current context. The one public route is the [invitation lookup](#public-invitation-lookup), where the token is the credential.

Roster defines no gate for a UI: Atrium v2 owns its own access gate, and a frontend of your own decides who may open it.

### Policies

Each Roster model has a policy. The core models' policies are registered with the Gate from `roster.policies`; package resources register their own policies (see [Extending Roster](#extending-roster)), and an entry for one of their models in `roster.policies` still wins. Point a model at a class of your own to change who may do what:

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
    // Roster's API routes and MCP tools pass the organization or team
    // the call is about ($subject), and the user it is about when everyone may
    // do it to themselves ($self). Your own checks pass the record.
    public function delete(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $user?->email === 'founder@example.com';
    }
}
```

**Roster's own checks ask them.** On every surface, permission `roster.{resource}.{verb}` asks the policy of the resource's model, by the camel-cased verb: `roster.organizations.update` asks `update()` on `OrganizationModel`'s policy, `roster.users.manage-status` asks `manageStatus()` on `UserModel`'s. The core resources are `users` (`UserModel`, whatever your user model is), `organizations`, `members` (`MembershipModel`), `teams`, `invitations` and `roles` (which covers permissions). Other packages register their own resources the same way (see [Extending Roster](#extending-roster)). The other models' policies (profiles, domains, links, seats, assignments, permissions, impersonations, transfers and their rows) answer your own checks: `$user->can('manage', $domain)`.

The bundled policies answer with Roster's permissions, so nothing changes until you swap one. Since checks now go through the Gate, your app's `Gate::before` callbacks apply to them too. Some things stay with Roster whatever a policy says: permissions blocked while impersonating are refused first, `roster.audit.view` has no model and is checked directly, and the organization-limited lists above still work out the caller's organizations from their roles. Remove a model's entry to check its resource by Roster's permissions alone.

**Organization admins.** A permission held only in some organizations (through an organization role, or by owning the organization) still opens the lists of organizations, roles, impersonations and transfers. Asked without an `organization`, those lists hold only what falls within the user's organizations: their organizations, the shared roles plus those organizations' own, and so on. On both surfaces, the API and MCP, and in `GET /roster/search/organizations`. Asking for another organization is still refused. Users and Permissions stay global, as do the users search and both stats endpoints.

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
- **Context:** `impersonator` while someone is impersonating, and `transfer` for a change made by a CSV import. Other packages add their own (see [Audit context](#audit-context)).
- **Surfaces:** the signed invitation and impersonation pages are recorded as `web`.
- **Redaction:** the users table's password column, on top of Keen's own list.

### Reading it

While Roster audits:

- `GET /roster/history` and `list-roster-history-tool` serve Roster's history, to holders of the global `roster.audit.view`. Roster renders no history itself; a UI reads it from there or from Keen.
- If your app hasn't defined the `viewAuditLog` Gate ability that Keen and every package's history ask, Roster defines it as the global `roster.audit.view` permission. Define your own to take over.

Otherwise the route answers 404, and the tool is not offered (to MCP clients or to Cortex).

Record your app's own events with Keen directly: `Keen::record('invoice.paid')->on($invoice)->in($organization)->save();`.

## SSO and SCIM (refactor-circus/guardian)

**Without Guardian, organizations that enforce SSO accept password sign-in.** Roster no longer ships single sign-on or SCIM provisioning, and adds no guard of its own: [`refactor-circus/guardian`](https://github.com/Refactor-Circus/Guardian) adds OpenID Connect, SAML 2.0 and Microsoft Entra ID sign-in, SSO enforcement and a SCIM 2.0 endpoint to Roster's organizations, through Roster's [extension seams](#extending-roster). Install it in the same deploy as this Roster version:

```bash
composer require refactor-circus/guardian
```

Upgrading from a Roster that still had them (the [CHANGELOG](CHANGELOG.md) has the full steps):

1. Install Guardian in the same deploy.
2. **Before running any migrate command**, delete your published `create_roster_sso_tables` and `create_roster_scim_tables` migrations and never roll them back (their `down()` drops the tables), and remove the SSO/SCIM keys from published copies of the two index migrations.
3. Publish and run Guardian's migrations, then `php artisan roster:sync-permissions`. Guardian renames the `roster_sso_*` / `roster_scim_*` tables and the `roster.sso.*` / `roster.scim.*` role grants to its own `guardian_*` / `guardian.*` names. If you installed Guardian before this Roster version, run `php artisan guardian:upgrade-from-roster` after upgrading Roster to finish the rename.
4. Move published `roster.sso` / `roster.scim` config to Guardian's keys, and check its SCIM prefix (Roster's was `scim/v2`).
5. Use Guardian's class, route and permission names in place of Roster's old ones.
6. Replace `NotSsoEnforced` in your login validation with `RefactorCircus\Roster\Support\Rules\NoExternalSignIn`.

Without Guardian nothing is deleted: the tables, their rows and the three permissions stay where they are, SSO sign-in and the SCIM endpoints answer 404, `roster:sync-permissions` reports the permissions as unregistered, and members provisioned through SCIM show their stored source, `scim`.

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
- **Only the fields you send are written:** `name`, `slug`, `domains`, `auto_join`, `status`, `status_reason` and `account_number`. Anything left out stays as it is. Local edits are allowed, and the next sync overwrites them.
- **Owners.** A new organization has no owner unless the record names one (`owner`, a user key). An owner is only applied to an organization that doesn't have one yet; sync never replaces an existing owner.
- **Several systems:** an organization can be linked to one record per source, for example `erp` and `crm`. Manage links by hand with `LinkOrganizationAction` / `UnlinkOrganizationAction`, `PUT` / `DELETE /roster/organizations/{organization}/links/{source}`, or the link tools.
- **Finding them:** `ListOrganizationsAction` and `GET /roster/organizations` take `source`, `external_id` and `account_number` filters.
- **Exporting:** the `export_organizations` CSV uses the same columns as the import, so you can export, edit and re-import.
- **In bulk:** `SyncOrganizationsAction` (`POST /roster/organizations/sync`) takes up to `roster.organizations.sync_batch` (500) records. Each record succeeds or fails on its own, and the response lists `created` / `updated` / `unchanged` / `error` (with messages) per record, in order. For files, use the `import_organizations` CSV import.
- **Permission:** the sync Actions and the CSV import need the global `roster.organizations.sync` permission. Give an integration user just that, plus `roster.organizations.view` if it needs to look organizations up. Linking and unlinking by hand need `roster.organizations.update` in the organization.
- **Audit:** with Keen installed, each sync records `organization.synced` with the source, id and outcome, next to the usual `organization.created` / `organization.updated` entries. A batch also records `organizations.synced` with its counts.

## Impersonation

Holders of `roster.users.impersonate` can temporarily act as another user to see what they see. Nobody gets it by default, so grant it deliberately: globally to impersonate anyone, or in an organization to impersonate its members.

```php
use RefactorCircus\Roster\Domains\Impersonation\Actions\StartImpersonationAction;
use RefactorCircus\Roster\Domains\Impersonation\Data\StartImpersonationData;

$started = app(StartImpersonationAction::class)->execute($user, new StartImpersonationData(reason: 'Ticket #4521'), actor: $admin);

return redirect($started->url); // a one-time link, valid for a few minutes
```

Over the API (`POST /roster/users/{user}/impersonate`) and MCP (`start-impersonation-tool`), the link is returned once for the operator to open.

**Safety rails**

- **The link only works in the impersonator's own browser** (signed in as them), works once, and expires after `link_minutes` (5). Only its hash is stored.
- **Refused targets:** yourself, inactive users, super-admins (unless you are one), and anyone holding a permission you lack. You can't impersonate while already impersonating.
- **A reason is required**, and the session ends on its own after `ttl_minutes` (30): on the impersonator's next request, or, if they never come back, when `php artisan roster:end-expired-impersonations` runs. Schedule it (every five minutes, say) so every expired session and unused link is ended on the record.
- **Blocked while impersonating:** the abilities in `roster.impersonation.blocked` are refused, even for super-admins. This works through Roster's checks and Laravel's Gate, so your own abilities can be listed too (wildcards like `billing.*`). The impersonated account's email and password can't be changed.
- **Ending it:** a global holder can end anyone's impersonation (`DELETE /roster/impersonations/{impersonation}` or `stop-impersonation-tool`). The browser switches back on its next request.

**The banner.** Show it in your own layout:

```blade
@include('roster::impersonation-banner')
```

It names who you're acting as and who you really are, shows the time left, and has a "Return to my account" button, which lands on `roster.impersonation.return_to` (`/` when unset). It is plain HTML styled inline, so it needs no stylesheet on your own pages. Pass `['bannerClass' => '...']` to add classes.

**Audit.** With Keen installed, everything done while impersonating is recorded as the impersonated user, with `context.impersonator` naming the real person. Starting, entering and ending each get their own entry.

The `roster.impersonation` middleware, which ends expired or revoked sessions, is added to the `web` group automatically. Set `roster.impersonation.middleware_group` to null to add it yourself.

## CSV import and export

Bulk-add members, users and teams from a CSV, and export members, users or organizations. Imports and exports run in the background as [`refactor-circus/impex`](https://github.com/Refactor-Circus/Impex) flows, so Impex is needed for this feature:

```bash
composer require refactor-circus/impex
php artisan vendor:publish --tag="impex-migrations"
php artisan migrate
```

Run a queue worker, and schedule Laravel's scheduler (Impex's `impex:tick` expires unconfirmed imports) and `roster:prune-transfers`. Without Impex, the transfer Actions throw a `TransfersUnavailableException` that names the missing package.

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

**Templates.** Every import type has a CSV template: the header row, plus commented example rows. Rows whose first cell starts with `#` are ignored, so an untouched template imports nothing; it's refused as having no rows. Download them from `GET /roster/imports/templates/{type}`, or with `show-import-template-tool`. Any signed-in user may download them, since they hold no data. To change them (for example to match your own wording or examples), publish them:

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

- **No passwords.** A file with a `password` column is refused. New accounts get a random password; people reset it or sign in another way their app offers.
- **Accounts outside the organization's domains are never claimed.** Those rows become invitations.
- **Limits:** `max_bytes` (5 MB) and `max_rows` (10,000). UTF-8 with or without a BOM is accepted.
- **Formula injection:** exported cells starting with `=`, `+`, `-`, `@`, a tab or a carriage return get a leading `'`, so spreadsheets show them as text.
- **Files** live on `roster.transfers.disk` (keep it private) under `roster/transfers/{id}`. They are only served through Roster's authorized download route, to whoever started the transfer or holds its permission in scope. MCP gets a signed link valid for 15 minutes. `roster:prune-transfers` deletes files older than `retention_days` (7).

**Audit.** Every row is applied as the person who confirmed, so with Keen installed it is recorded as theirs, with surface `import` and `context.transfer` naming the import. Starting, confirming, cancelling and finishing each have their own entry.

## Deleting and restoring

Deleting a user or an organization is recoverable. It goes to **Deleted** (list it with `trashed=only` on `GET /roster/users` / `GET /roster/organizations`, or `trashed=with` for everything), and can be restored with everything it had until it's deleted permanently.

- **Users** need Laravel's `SoftDeletes` on the user model. Roster's bundled `RefactorCircus\Roster\Domains\User\Models\UserModel` has it; add it to your own model and a `deleted_at` column (`$table->softDeletes()`):

  ```php
  use Illuminate\Database\Eloquent\SoftDeletes;

  class User extends Authenticatable
  {
      use HasRoster, SoftDeletes;
  }
  ```

  Laravel then hides deleted users everywhere, sign-in included. Their profile, memberships, team seats, roles and personal organization are kept for a restore. A model without the trait is deleted permanently, as before, and the warning says so.
- **Organizations** always soft-delete. A deleted organization is switched off: its pages and API 404, its members lose access, and its domain auto-join stops. **Its slug and domains stay reserved**, so a restore always works.
- **Restore** with `RestoreUserAction` / `RestoreOrganizationAction`, `POST /roster/users/{user}/restore` / `POST /roster/organizations/{organization}/restore`, or the MCP restore tools. It needs the same permission as deleting.
- **Delete permanently** with `PurgeUserAction` / `PurgeOrganizationAction`, `DELETE …/purge` or the MCP purge tools. Only deleted records can be purged, and it needs `roster.users.purge` / `roster.organizations.purge`.
- **Organizations are purged in the background**, since a large one takes all its teams, members, invitations, roles and connections with it: the API and MCP check the request at once (a refusal answers right away), then queue a `PurgeOrganizationJob` and answer `202 Accepted`, so the organization disappears shortly after. Call `PurgeOrganizationAction::queue($organization)` to do the same, or `execute()` to purge in place. Run a queue worker. An organization restored before the job runs is kept.
- **Automatically:** schedule `php artisan roster:purge-deleted` to permanently delete records deleted more than `roster.deletes.retention_days` days ago (organizations are queued, one job each). The default, `null`, keeps deleted records forever; `--days=N` overrides it for one run.

## Approving new users

New accounts can wait for an approver instead of becoming active straight away. They then have the status `pending` ("Awaiting approval"). Who starts pending depends on how the account was made:

| Created by | Starting status |
|---|---|
| The app's own registration (Laravel's `Registered` event, which Breeze, Fortify and Jetstream fire) | `roster.users.registration_status`: `active` (default) or `pending` |
| An admin: `CreateUserAction`, the API or MCP | Their choice, through the `status` input (`active` by default) |
| A package that provisions accounts for an organization from outside (refactor-circus/guardian, for one) | That organization's `provisioned_status` setting (`active` by default) |

CSV imports create active users.

- **Approving and rejecting:**
  - `ApproveUserAction` makes a pending account active.
  - `RejectUserAction` deactivates it with an optional `reason`. The account is kept and can be reactivated later, and the email stays taken.
  - Both need the global `roster.users.approve` permission. Organization admins don't get it, because accepting an account is a site decision.
  - Over the API: `POST /roster/users/{user}/approve` and `POST /roster/users/{user}/reject`; list who is waiting with `GET /roster/users?status=pending`.
- **While pending:**
  - `roster.active` answers 403 with "Your account is awaiting approval".
  - Reactivating a pending account is refused; approve it instead.
- **Emails,** each switchable in `roster.users.approvals`:
  - `notify_approvers` tells everyone with `roster.users.approve` (super-admins included) when someone is waiting. Its button opens `roster.users.approvals.review_url` (e.g. `https://admin.test/users/{user}`, with `{user}` replaced by the waiting user's route key), a page of your UI; while it is `null` (the default) the email has no button.
  - `notify_user` tells people when they're approved or rejected.

## Blocking inactive users

```php
Route::middleware(['auth', 'roster.active'])->group(...);
```

Suspended and deactivated users get a 403, and so do users awaiting approval (with their own message). A user with no profile row is active. With your own status enum, every status whose `allowsAccess()` is false is refused.

## Statuses

Users, organizations and teams each have a `status` (with `status_reason` and `status_changed_at`). Which statuses exist is up to you: each is a string-backed enum named under `roster.statuses`.

| Key | Default enum | Contract | Cases |
|-----|--------------|----------|-------|
| `users` | `Domains\User\Enums\UserStatus` | `RefactorCircus\Roster\Contracts\AccountStatus` | `active`, `pending`, `suspended`, `deactivated` |
| `organizations` | `Domains\Organization\Enums\OrganizationStatus` | `RefactorCircus\Roster\Contracts\Status` | `active`, `inactive` |
| `teams` | `Domains\Team\Enums\TeamStatus` | `RefactorCircus\Roster\Contracts\Status` | `active`, `inactive` |

`Status` asks for a `label()`, a `badge()` variant for a UI to show it with (`success`, `warning`, ...) and the `default()` new records start with. `AccountStatus` adds the cases Roster's flows move users between (`active()`, `pending()`, `suspended()`, `deactivated()`) and `allowsAccess()`, so an enum can rename those cases or add its own, such as a `trial` that lets users in:

```php
use RefactorCircus\Roster\Contracts\Status;

enum CustomerStatus: string implements Status
{
    case Active = 'active';
    case OnHold = 'on_hold';
    case Blocked = 'blocked';

    public static function default(): static { return self::Active; }
    public function label(): string { return __('customers.status.'.$this->value); }
    public function badge(): string { return $this === self::Active ? 'success' : 'warning'; }
}

// config/roster.php
'statuses' => ['organizations' => CustomerStatus::class, /* ... */],
```

- **Organization and team statuses are data only.** Roster stores, shows and filters by them, but grants and refuses nothing by them; check them in your own middleware or policies if they should block anything.
- **Setting them:** pass `status` (and optionally `status_reason`) to `CreateOrganizationData`, `UpdateOrganizationData`, `SyncOrganizationData`, `CreateTeamData` and `UpdateTeamData`, or the matching API routes and MCP tools. A changed status stamps `status_changed_at`; the reason is kept until you send another.
- **Finding them:** `ListOrganizationsAction` / `ListTeamsAction` (and their routes and tools) take a `status` filter.
- **Code** reads the configured enum through `RefactorCircus\Roster\Support\Statuses`: `Statuses::users()::pending()`, `Statuses::organization('inactive')`.
- **Changing enums** only changes what new values may be. Rows keep the value they hold, and reading a row whose value is no longer a case fails, so move rows off a status before removing it. New organizations and teams default to the enum's `default()`; the migration gives existing rows the default of the enum configured when it runs.

## HTTP API and MCP

> **Security:** both are **off by default**: `roster.routes.enabled` defaults to `false`, and so do `roster.mcp.web.enabled` and `roster.mcp.local.enabled`. Every route and tool checks the caller's Roster permissions, but that needs an authenticated user, so add authentication middleware before enabling them.

```php
'routes' => ['enabled' => true, 'prefix' => 'roster', 'middleware' => ['api', 'auth:sanctum', 'throttle:roster']],
'mcp' => ['web' => ['enabled' => true, 'route' => 'mcp/roster', 'middleware' => ['api', 'auth:sanctum', 'throttle:roster']]],
```

The API is what a UI builds on, Atrium v2 included: besides one route per Action, it has a few read-only routes a UI needs, such as user and organization counts (`stats/*`), a typeahead search (`search/*`), every role assignment of an organization, and the public invitation lookup. Its shape is the committed [OpenAPI document](#openapi-document-and-typescript-client).

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
| GET | `/roster/stats/users` | `roster.stats.users` (`total` and `by_status`, every configured status; cached a minute; global `roster.users.view`) |
| GET | `/roster/search/users?q=&limit=` | `roster.search.users` (user summaries whose name or email starts with `q`, 1-100 characters, matched literally; `limit` 1-25, default 5; global `roster.users.view`) |
| GET, POST | `/roster/organizations` | `roster.organizations.index`, `.store` |
| GET, PATCH, DELETE | `/roster/organizations/{organization}` | `roster.organizations.show`, `.update`, `.destroy` |
| POST | `/roster/organizations/{organization}/transfer` | `roster.organizations.transfer` |
| POST | `/roster/organizations/{organization}/restore` | `roster.organizations.restore` |
| DELETE | `/roster/organizations/{organization}/purge` | `roster.organizations.purge` (deleted organizations only; 202, purged by a queued job) |
| PUT | `/roster/organizations/external/{source}/{externalId}` | `roster.organizations.sync` (upsert; 201 when created) |
| POST | `/roster/organizations/sync` | `roster.organizations.sync-many` (`{records: [...]}`) |
| PUT, DELETE | `/roster/organizations/{organization}/links/{source}` | `roster.organizations.links.update`, `.destroy` |
| GET | `/roster/stats/organizations` | `roster.stats.organizations` (`organizations`, `teams` and `pending_invitations`, deleted organizations left out; global `roster.organizations.view`) |
| GET | `/roster/search/organizations?q=&limit=` | `roster.search.organizations` (organizations whose name or slug starts with `q`, by name, deleted ones left out; same `q` and `limit` rules as `search/users`; limited to the organizations where the caller holds `roster.organizations.view`) |
| GET, POST | `/roster/organizations/{organization}/members` | `roster.organizations.members.index`, `.store` |
| DELETE | `/roster/organizations/{organization}/members/{user}` | `roster.organizations.members.destroy` |
| GET, POST | `/roster/organizations/{organization}/teams` | `roster.organizations.teams.index`, `.store` |
| GET, PATCH, DELETE | `/roster/organizations/{organization}/teams/{team}` | `roster.organizations.teams.show`, `.update`, `.destroy` |
| POST | `/roster/organizations/{organization}/teams/{team}/members` | `roster.organizations.teams.members.store` |
| DELETE | `/roster/organizations/{organization}/teams/{team}/members/{user}` | `roster.organizations.teams.members.destroy` |
| GET, POST | `/roster/organizations/{organization}/invitations` | `roster.organizations.invitations.index`, `.store` |
| DELETE | `/roster/organizations/{organization}/invitations/{invitation}` | `roster.organizations.invitations.revoke` |
| GET | `/roster/invitations/{token}` | `roster.invitations.lookup` (public, token only; see [Public invitation lookup](#public-invitation-lookup)) |
| POST | `/roster/invitations/{token}/accept` | `roster.invitations.accept` (as the authenticated user) |
| POST | `/roster/invitations/{token}/decline` | `roster.invitations.decline` (as the authenticated user) |
| GET, POST | `/roster/permissions` | `roster.permissions.index`, `.store` |
| PATCH, DELETE | `/roster/permissions/{permission}` | `roster.permissions.update`, `.destroy` |
| GET, POST | `/roster/roles` | `roster.roles.index`, `.store` |
| GET, PATCH, DELETE | `/roster/roles/{role}` | `roster.roles.show`, `.update`, `.destroy` |
| GET, POST | `/roster/users/{user}/roles` | `roster.users.roles.index`, `.store` |
| DELETE | `/roster/users/{user}/roles/{assignment}` | `roster.users.roles.destroy` |
| GET | `/roster/users/{user}/permissions` | `roster.users.permissions` |
| GET | `/roster/organizations/{organization}/role-assignments` | `roster.organizations.role-assignments.index` (every role assignment in the organization and its teams, paginated like `users/{user}/roles`, each item the same; `roster.roles.view` in the organization) |
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

`{user}` is the user model's route key; `{organization}` and `{team}` are slugs; `{role}`, `{assignment}`, `{invitation}` and `{entry}` are ids; `{permission}` is the permission name. Responses use a `data` envelope. The MCP server offers one tool per Action, except the public invitation lookup (an agent acts as a signed-in user, who has no use for it) and the two typeahead searches (agents page through `list-users-tool` and `list-organizations-tool`, whose `search` matches anywhere), and each tool returns the same payload as the HTTP API as structured content: the record under `data`, a list's page under `meta` (`current_page`, `last_page`, `per_page`, `total`), and extras such as a sync's `outcome` beside them. Every tool that answers this way declares an output schema, so an agent knows each field, its type and its allowed values before calling it. Deletes and the import template answer with text.

MCP tools (all behind Laravel MCP's tool search):

| Area | Tools |
|---|---|
| Users | `list-users-tool`, `show-user-tool`, `create-user-tool`, `update-user-tool`, `delete-user-tool`, `update-profile-tool`, `suspend-user-tool`, `deactivate-user-tool`, `reactivate-user-tool`, `approve-user-tool`, `reject-user-tool`, `restore-user-tool`, `purge-user-tool`, `show-user-stats-tool`, `switch-context-tool`, `join-by-domain-tool` |
| Organizations | `list-organizations-tool`, `show-organization-tool`, `create-organization-tool`, `update-organization-tool`, `delete-organization-tool`, `restore-organization-tool`, `purge-organization-tool`, `show-organization-stats-tool`, `transfer-ownership-tool`, `list-members-tool`, `add-member-tool`, `remove-member-tool`, `sync-organization-tool`, `sync-organizations-tool`, `link-organization-tool`, `unlink-organization-tool` |
| Teams | `list-teams-tool`, `show-team-tool`, `create-team-tool`, `update-team-tool`, `delete-team-tool`, `add-team-member-tool`, `remove-team-member-tool` |
| Invitations | `list-invitations-tool`, `create-invitation-tool`, `revoke-invitation-tool`, `accept-invitation-tool`, `decline-invitation-tool` |
| Impersonation | `start-impersonation-tool`, `list-impersonations-tool`, `stop-impersonation-tool` |
| History | `list-roster-history-tool` (reads refactor-circus/keen) |
| CSV import and export | `show-import-template-tool`, `start-import-tool`, `confirm-import-tool`, `start-export-tool`, `list-transfers-tool`, `show-transfer-tool`, `cancel-transfer-tool` |
| Roles | `list-permissions-tool`, `create-permission-tool`, `update-permission-tool`, `delete-permission-tool`, `list-roles-tool`, `show-role-tool`, `create-role-tool`, `update-role-tool`, `delete-role-tool`, `list-role-assignments-tool`, `assign-role-tool`, `revoke-role-tool`, `list-user-permissions-tool` |

### Changing a user's status

There is no status endpoint: each target status has its own route and tool, which all need the global `roster.users.manage-status` permission. Suspending and deactivating take an optional `reason`.

| Target status | Route | Request | MCP tool |
|---|---|---|---|
| `active` | `roster.users.reactivate` | `POST /roster/users/{user}/reactivate` | `reactivate-user-tool` |
| `suspended` | `roster.users.suspend` | `POST /roster/users/{user}/suspend` | `suspend-user-tool` |
| `deactivated` | `roster.users.deactivate` | `POST /roster/users/{user}/deactivate` | `deactivate-user-tool` |

A user awaiting approval (`pending`) is not reactivated: approve them with `roster.users.approve` (`POST /roster/users/{user}/approve`), or reject them with `roster.users.reject`, both needing the global `roster.users.approve` permission. A user who is already in the target status, or yourself, is refused with a 422.

### Public invitation lookup

`GET /roster/invitations/{token}` (`roster.invitations.lookup`) shows an invitation to whoever holds its token, so a page of your own (`roster.invitations.accept_url`, Atrium v2's for one) can greet a guest who opened the email before they sign in. Unlike the rest of the API it needs no signed-in user, so it is built not to leak:

- **Public, but only with the API.** It is registered only while `roster.routes.enabled` is `true`, under `roster.routes.prefix`, and wrapped in `roster.invitations.lookup_middleware` (default `['api', 'throttle:roster-invitation-lookup']`), never in `roster.routes.middleware`, so the authentication you add to the API does not apply to it. Don't add authentication to it: the guest is not signed in yet, and the token is the credential.
- **Tokens can't be enumerated.** The route only matches tokens of 48 alphanumeric characters, so anything else is a 404 before any query runs. Roster looks the token up by its hash and never compares plain tokens. An unknown token and an expired, revoked, accepted or declined invitation, or one whose organization is deleted, all get the same 404 status and body.
- **Rate-limited per IP.** The `roster-invitation-lookup` limiter allows `roster.rate_limit.invitation_lookups_per_minute` (default 10) requests per minute per IP, valid tokens or not, then answers 429. Null turns it off; don't, since it is what stops tokens being guessed.
- **A minimal, uncached payload.** It answers only `{"data": {"email", "organization": {"name"}, "teams": [names, sorted], "expires_at"}}` (`email`, `organization.name`, `teams`, `expires_at`) with `Cache-Control: no-store`. It never holds the token, an id or slug, the inviter, the status or any other timestamp, and the two events the lookup dispatches never carry the token either.
- **The token is in the URL**, so your web server's and proxies' access logs may record it. Keep `/roster/invitations/*` out of your access logs. Tokens expire (`roster.invitations.expires_after_days`), and only a signed-in user whose email matches can accept one.

Accepting and declining stay authenticated: `POST /roster/invitations/{token}/accept` and `.../decline` act as the signed-in user.

### Rate limiting

The default API and MCP middleware include `throttle:roster`: 120 requests per minute per signed-in user, or per IP for guests. Change it with `roster.rate_limit.per_minute` (null turns it off). The public invitation lookup has its own per-IP limiter, `throttle:roster-invitation-lookup` (see above). Missing records answer `404 {"message": "Not found."}` without naming internal classes.

## OpenAPI document and TypeScript client

Roster commits an OpenAPI 3.1 document of its JSON API, `openapi.json` (title "Roster API", version 2.0.0), and a TypeScript client generated from it, in `client/`. Atrium v2's Nuxt layer uses them, and so can any frontend of yours.

- **The document** has one operation per `roster.*` API route, the public invitation lookup included, and none for the web routes (the signed invitation page, impersonation and the signed transfer-file link). Each `operationId` is the route name (`roster.users.index`), and its single server is the relative `/roster`, so the paths are relative to your `roster.routes.prefix`. Response schemas are inferred by [Scramble](https://scramble.dedoc.co), so a few payloads are typed loosely (`object`) until resources export their schemas to it.
- **The client** is the npm package `@refactor-circus/roster-client` (version 2.0.0), TypeScript source with no build step. `createRosterClient(options)` wraps [`openapi-fetch`](https://openapi-ts.dev/openapi-fetch/)'s `createClient<paths>()`, and the package re-exports the generated `paths` and `components` types:

  ```ts
  import { createRosterClient, type components } from '@refactor-circus/roster-client';

  const roster = createRosterClient({
      baseUrl: 'https://app.test/roster', // your roster.routes.prefix included
      credentials: 'include',             // or headers: { Authorization: `Bearer ${token}` }
  });

  const { data, error } = await roster.GET('/search/users', { params: { query: { q: 'ada', limit: 5 } } });
  ```

  `options` takes `baseUrl` and, optionally, `fetch`, `headers` and `credentials`.
- **Installing it.** It is not published to npm, and npm cannot install a subdirectory of a git repository. With pnpm, pin a Roster tag and the folder: `pnpm add "github:Refactor-Circus/Roster#v2.0.0&path:/client"`. Install its dependencies (`openapi-fetch`) with it.

**Regenerating.** After changing a route, request or resource, run:

```bash
composer client:generate
```

It exports `openapi.json` from the Testbench workbench with Scramble (`php vendor/bin/testbench workbench:openapi openapi.json`: Scramble is a dev dependency configured in the workbench, and the file is pretty-printed with sorted keys, so diffs stay small), then runs `npm --prefix client ci` and `npm --prefix client run generate`, which writes `client/src/schema.d.ts` with the sha256 of `openapi.json` on its first line. Check the types with `npm --prefix client run typecheck`, and commit `openapi.json` and `client/` together. `tests/Feature/OpenApiTest.php` fails when a fresh export differs from the committed `openapi.json`, or the sha256 in `schema.d.ts` differs from the committed file's, and CI's `client` job fails on any diff the generator leaves in `client/`.

## Cortex

When [`refactor-circus/cortex`](https://github.com/Refactor-Circus/cortex) is installed, Roster registers its MCP server and every tool with Cortex. Nothing needs registering in your app. Cortex agents can then manage users, organizations and roles, and you can publish new versions of the server instructions and tool descriptions in Cortex.

- Agents act as the **signed-in user**, so Roster's permission checks apply. A run with no signed-in user is refused.
- Limit what agents see with `roster.cortex.tools` (a list of tool names), or turn the integration off with `roster.cortex.enabled`.

Roster renders no UI for Cortex: Cortex's own screens are Cortex's.

## Extending Roster

Other packages, and your app, add authorization resources, permissions, membership sources, sign-in requirements and audit context to Roster through registration points, without Roster naming them; refactor-circus/guardian, for one, adds single sign-on and provisioning this way. Register from a service provider's `boot()`, never per request. Each registry is a singleton holding code-defined state for the life of the process (Octane, queue workers), and registering the same thing twice throws.

**`roster.*` is Roster's.** Resource prefixes and permission names under `roster.*` are reserved for Roster's own domains: `resource()` and `permission()` throw an `InvalidArgumentException` naming the value for any of them. Register under your package's own prefix, such as `acme.widgets`.

### Resources

A resource lets your model's Gate policy answer the permissions named after it in every one of Roster's checks: API and MCP requests and `Authorizer::check()`. Register a prefix with its model, and permission `{prefix}.{verb}` asks that model's policy by the camel-cased verb, exactly as `roster.users.manage-status` asks `UserPolicy::manageStatus()`:

```php
use Acme\Widgets\Models\WidgetModel;
use Acme\Widgets\Policies\WidgetPolicy;
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Support\Authorization\Resources;

public function boot(): void
{
    app(Resources::class)->resource('acme.widgets', WidgetModel::class);
    Gate::policy(WidgetModel::class, WidgetPolicy::class);
}
```

```php
use Illuminate\Database\Eloquent\Model;
use RefactorCircus\Roster\Support\Policies\Policy;

final class WidgetPolicy extends Policy
{
    // `acme.widgets.approve`, checked in the widget's organization or team.
    public function approve(?Model $user, ?Model $subject = null, ?Model $self = null): bool
    {
        return $this->permits($user, 'acme.widgets.approve', $subject, $self);
    }
}
```

- A prefix is two lower-case segments of letters, digits and dashes (`acme.widgets`). An invalid prefix, a `roster.*` one, or one already registered throws an `InvalidArgumentException` naming it.
- A permission's prefix is everything before its last dot, so `acme.widgets.approve.all` looks up `acme.widgets.approve`. A permission whose prefix nothing registers asks no policy and is refused.
- Register the model's policy too. A registered model without one makes the check throw an `InvalidArgumentException` naming the resource, the first time a permission resolves to it. A missing policy never counts as an allow.
- Asked with a record, `permits()` finds its organization or team through its `organization_id` / `team_id` attribute or its `organization()` / `team()` relation, whichever it has, as for Roster's own models.
- A resource registered after checks have run still counts: `Resources` keeps its own `generation()`, and the answers remembered for the request start over when it changes.

### Permissions

Add your permissions to Roster's catalogue so they are created as system permissions, and say which built-in roles (`admin`, `member`, `lead`) they go to:

```php
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;

public function boot(): void
{
    app(PermissionCatalogue::class)
        ->permission('acme.widgets.view', 'See widgets.', ['admin', 'member'])
        ->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);
}
```

- The role-tables migration, `BuiltInRoles::sync()` and `php artisan roster:sync-permissions` create them. A package registered after that migration ran (installed later, or upgraded with new permissions) needs `php artisan roster:sync-permissions` to create its permissions.
- Existing built-in roles never gain a newly registered permission automatically: the grants apply only to the built-in roles the sync creates, so grant the permission to existing roles by hand, through the role API or MCP tools. Grants an app has changed are never altered.
- A name registered twice, a `roster.*` name, or a role that is not built in throws an `InvalidArgumentException` naming it. `roster:sync-permissions` warns about system permissions in the database that nothing registers any more, and leaves them in place.
- The impersonation-blocked list stays core-only: registered permissions are never added to `roster.impersonation.blocked`. If yours must not run while someone is impersonating, add its pattern there yourself (`'acme.widgets.*'`).

### Membership sources

A membership records how the user joined. Register your package's way in, with the label members show for it:

```php
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;

public function boot(): void
{
    app(MembershipSources::class)->register('hris', 'HR system');
}
```

- `$membership->source` is the stored string. Roster's built-in sources are the `MembershipSource` cases (`direct`, `invitation`, `domain`, `personal`, `import`), and `$membership->sourceEnum()` answers the case, or null for a registered source such as `hris`.
- The API and MCP member data show the registered label (`HR system`). A stored source that nothing registers, from a package you removed for example, shows its stored value and never errors.
- Code that joins users through Roster's `ManagesMemberships::join()` passes a registered source as a string. An unregistered one throws an `InvalidArgumentException`, as does registering a built-in value or the same source twice.

### Sign-in requirements

A sign-in requirement says that an email must sign in somewhere else, such as an identity provider, instead of with a password. Guardian's single sign-on enforcement is one. Roster has no password login of its own; add the `NoExternalSignIn` rule to yours, and it fails for any email a registered sign-in requirement claims, with a link to where that sign-in starts:

```php
use RefactorCircus\Roster\Support\Rules\NoExternalSignIn;

$request->validate(['email' => ['required', 'email', new NoExternalSignIn]]);
```

Register your own requirement the same way Guardian does:

```php
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;

final class PartnerSignIn implements SignInRequirement
{
    public function for(string $email): ?ExternalSignIn
    {
        if (! str_ends_with(strtolower($email), '@partner.example')) {
            return null;
        }

        return new ExternalSignIn(key: 'partner', label: 'Partner Inc.', url: route('partner.sign-in'));
    }
}
```

```php
use RefactorCircus\Roster\Facades\Roster;

public function boot(): void
{
    Roster::requireSignIn(PartnerSignIn::class);
}

Roster::externalSignInFor('ada@partner.example')?->url; // where her sign-in starts
```

- `externalSignInFor()` asks the requirements in registration order and returns the first answer that isn't null. Requirements must not overlap: make sure no two claim the same emails. A class-string is resolved from the container once, on first use, and registering the same class or instance again is ignored.
- Super-admins get `null` before any requirement is asked, so a broken requirement can't lock them out.
- It fails closed. A requirement that throws is not caught: the login or registration asking (through `NoExternalSignIn`) fails, Laravel's handler reports the exception, and the user sees a generic error, never its message. This is deliberate for a security control, so keep requirements simple.
- The `NoExternalSignIn` rule fails with the translated `roster::roster.external_sign_in_required` message, its `:organization` from the answer's `label` and `:url` from its `url`. `ExternalSignIn::$record` holds whatever record a registered sign-in requirement keeps behind its answer, if any.
- With no requirement registered (no Guardian, say), every email passes the rule, so organizations that enforced single sign-on accept password sign-in.

### Provisioning status

`Roster::provisionedStatus()` is the provisioning API. Accounts your package creates for an organization from outside (externally provisioned accounts, such as Guardian's) start with the status it returns:

```php
use RefactorCircus\Roster\Facades\Roster;

$status = Roster::provisionedStatus($organization); // an AccountStatus, such as UserStatus::Pending
```

It is the organization's `provisioned_status`, resolved through the user statuses in `roster.statuses.users`. An organization with none starts its accounts as `active()`. A stored value the status enum doesn't know falls back to `active()` and is reported, so provisioning never fails and a bad value is never silent.

### Audit context

Roster has no audit API. To add context to the audit entries of Roster's changes, register a closure on refactor-circus/keystone's `AuditHooks`. `AuditHooks` runs every context closure for every package's events and catches nothing, so the closure keeps to Roster's entries itself and never lets an exception out:

```php
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Packages\PackageRegistry;
use RefactorCircus\Roster\Facades\Roster;

public function boot(): void
{
    // Once every provider has booted, when Roster registers its own hooks.
    $this->app->booted(function (): void {
        if (! Roster::auditing()) {
            return;
        }

        app(AuditHooks::class)->context(function (object $event): array {
            try {
                if (app(PackageRegistry::class)->for($event)?->key !== 'roster') {
                    return [];
                }

                $device = app(DeviceContext::class)->current();

                return $device === null ? [] : ['acme_device' => ['id' => $device->id, 'name' => $device->name]];
            } catch (Throwable $e) {
                report($e);

                return [];
            }
        });
    });
}
```

- Return `[]` when you have nothing to add, never `null`: `AuditHooks` spreads every closure's result into the entry.
- On a failure the closure reports the exception and the entry is still written, without your key.

## Trying it locally

The workbench is a demo app with seeded data and the JSON API turned on:

```bash
composer serve
```

It signs you in as the super-admin `admin@example.com` (password `password`) and opens `/`. You'll find organizations Acme and Globex with teams, members and a pending invitation, plus a custom `invoices.edit` permission and a Billing role. The JSON API is mounted at `/roster` (try `/roster/stats/users`). For a UI, point Atrium v2 at it. To run the MCP server over stdio:

```bash
php vendor/bin/testbench mcp:start roster
```

## Testing

```bash
composer test             # static analysis, formatting, type coverage, Pest
composer client:generate  # regenerate openapi.json and the TypeScript client after an API change
```

`composer test` fails when `openapi.json` or `client/src/schema.d.ts` is out of date (`tests/Feature/OpenApiTest.php`); regenerate them as described under [OpenAPI document and TypeScript client](#openapi-document-and-typescript-client).

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
