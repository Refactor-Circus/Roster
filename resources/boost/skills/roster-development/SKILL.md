---
name: roster-development
description: >
  Configure and apply the Roster package in Laravel applications: user CRUD,
  profiles, suspend/deactivate/reactivate, organizations, teams, invitations,
  domain auto-join, current org/team context, roles and permissions through
  Laravel's Gate, audit history (through refactor-circus/keen), impersonation, SSO, SCIM, CSV import/export,
  the roster.active and roster.organization middleware, and Roster's HTTP
  API, MCP server and Atrium screens.
license: MIT
metadata:
  author: Jay Fletcher
---

# Roster

Use this skill when a Laravel application manages users, profiles, account status, organizations/teams (tenancy), memberships or invitations with the `refactor-circus/roster` package.

## Primary Goal

- Use the `refactor-circus/roster` package's public API in the smallest correct way. Every operation is an Action in its domain (`RefactorCircus\Roster\Domains\{Domain}\Actions`, e.g. `Domains\Organization\Actions\CreateOrganizationAction`); call the Action instead of writing the queries yourself.

## Workflow

### 1. Inspect the Laravel app context

- Confirm `refactor-circus/roster` is in `composer.json` and find the user model (`config('roster.users.model')`, default `App\Models\User`).
- Check the user model's primary key type and column names.

### 2. Install and configure

```bash
php artisan vendor:publish --tag="roster-config"
php artisan vendor:publish --tag="roster-migrations"
php artisan migrate
```

- Set `roster.users.key_type` (`int` | `ulid` | `uuid`) **before** migrating; it decides the type of the `roster_profiles.user_id` column.
- If the users table uses other column names, set `roster.users.columns` (`name`, `email`, `password`; set `name` to `null` if there is none).
- Rename any table under `roster.tables` (`organizations`, `memberships`, `team_members`, `permission_role`, ...) **before** migrating; migrations, models, rules and queries read the same keys. Never hard-code `roster_*` names: use the models, or `RefactorCircus\Roster\Support\Tables::name('memberships')`.
- Pick a user mode:
  - Add `use RefactorCircus\Roster\Domains\User\Concerns\HasRoster;` to the user model (recommended). It provides `roster()`, `rosterStatus()`, `isRosterActive()` and the `rosterProfile` relation.
  - Or leave the model untouched. Roster registers `rosterProfile` dynamically; use `app(RefactorCircus\Roster\Support\Users::class)->profile($user)` / `->status($user)`.
  - Or, with no users table, use `RefactorCircus\Roster\Domains\User\Models\UserModel` and `php artisan vendor:publish --tag="roster-users-migration"`.

### 3. Apply the Actions

- Resolve with `app(Action::class)->execute(...)`. Input is never an array: each Action takes its Data object, `{Action}Data` in the domain's `Data` namespace (`SuspendUserAction` → `Domains\User\Data\SuspendUserData`). Build it with `new XData(named: args)` from your own values, or `XData::from($request->all())` from untrusted input, which validates against `XData::rules()` and throws `ValidationException`. Some rules take context: `UpdateUserData::from($input, $user)` lets the user keep their own email.
- Enum-valued fields take the enum (`RoleScope::Organization`, `TransferType::ImportMembers`, `UserStatus::Pending`). Update Data defaults fields to `RefactorCircus\Keystone\Data\Missing`: leave a field out to keep it, pass `null` to clear it; check with `$data->has('field')`.
- Pass the acting user as `actor`. Status changes and deletes then refuse to act on yourself.
- Business-rule failures throw `ValidationException`; let Laravel render them.

### 4. Organizations, teams and invitations

- Create organizations with `CreateOrganizationAction` (`owner` is a user route key, or pass `owner:` a model). The owner becomes a member; only `TransferOwnershipAction` changes the owner.
- Add members with `AddMemberAction`, then seat them on teams with `AddTeamMemberAction`; non-members are rejected.
- List members a page at a time with `ListMembersAction($organization, new ListMembersData(team: 'ops'))` for one team's; `ShowTeamAction` carries only a team's first 100 members (`members_count` is the full count).
- Invite with `CreateInvitationAction($organization, new CreateInvitationData(email: ..., teams: [slugs]), actor: $user)`. The email links to Roster's signed page (behind `roster.invitations.middleware`); only a signed-in user with the invited email can accept. Use `roster.invitations.accept_url` for a custom page that calls `AcceptInvitationAction`.
- Domain auto-join: set an organization's `domains` and `auto_join`. It fires only for verified emails on models implementing `MustVerifyEmail`.
- Read context with `$user->currentOrganization()` / `currentTeam()` (or `app(RefactorCircus\Roster\Roster::class)`); change it with `SwitchContextAction`.

### 5. Roles and permissions

- Declare app permissions with `CreatePermissionAction(new CreatePermissionData(name: 'invoices.edit'))`, bundle them with `CreateRoleAction(new CreateRoleData(name: ..., scope: RoleScope::Organization, permissions: [...]))` (`scope`: Global | Organization | Team; `organization` for an organization's own role), and assign with `AssignRoleAction($user, new AssignRoleData(role: $id, organization: slug, team: slug))`.
- Check with Laravel's Gate: `$user->can('invoices.edit', $organization)`, `@can`, `Gate::allows()`. Without an organization or team argument, the user's current context is used.
- Bootstrap: `php artisan roster:grant-super-admin {email}`. Run `php artisan roster:sync-permissions` after upgrading.
- Roster's own API, MCP tools and Atrium screens require the `roster.*` permissions (`roster.authorization`, on by default).
- Permissions and role assignments are remembered per request and forgotten by Roster's own Actions. Opt in to keeping them in the cache store with `roster.permission_cache.ttl` (seconds) / `.store`; after writing roles, assignments or permissions without Roster's Actions or models, call `Permissions::forgetCache()`. Eager-load `organization` / `team` on records you check one by one with `$user->can($ability, $record)`.
- With `refactor-circus/cortex` installed, every Roster tool is available to Cortex agents automatically. Agents act as the signed-in user; limit them with `roster.cortex.tools`.
- The API and MCP are throttled by `throttle:roster` (`roster.rate_limit.per_minute`). Keep it in custom `middleware` lists.

### 6. Auditing

- Roster has no audit log or audit code of its own. Install refactor-circus/keen (`composer require refactor-circus/keen`, `php artisan migrate`) and Keen records every Roster change, labelled, diffed (profile fields, role permissions, organization domains), scoped to its organization, and with the impersonator, import or SCIM token in its context. Without Keen there is no history anywhere in Roster.
- Turn it off with `roster.audit.enabled => false` (Keen then records nothing of Roster's). `Roster::auditing()` says whether Roster audits now.
- While it does, read history in Keen, on each Roster user, team and role page and the organization Activity tab (`roster.audit.view` in scope), or through `GET /roster/history` and `list-roster-history-tool` (global `roster.audit.view`). Roster defines the `viewAuditLog` Gate ability as global `roster.audit.view` unless the app defines it.
- Record the app's own events with Keen: `Keen::record('invoice.paid')->on($model)->in($organization)->with([...])->save();`. Add app secrets to `keen.redact`.

### 7. Organizations from external systems

- Sync an ERP or CRM record with `SyncOrganizationAction(new SyncOrganizationData(source: 'erp', externalId: $id, accountNumber: ..., name: ..., domains: [...]))`. It creates or updates by `source` + `external_id`, writes only the fields given, and returns `->outcome` (created/updated/unchanged) and `->organization`.
- Use `SyncOrganizationsAction(new SyncOrganizationsData(records: [...]))` for scheduled batches (each record an array, validated on its own); it returns per-record results and never fails the whole batch. Use the `import_organizations` CSV type for files; `export_organizations` writes the same columns (`filters.external_source` for one system), so exports re-import unchanged.
- Find organizations with `ListOrganizationsAction(new ListOrganizationsData(source: 'erp', accountNumber: 'A-42'))`. Each organization's `links` holds `source`, `external_id`, `account_number` and `synced_at`.
- Synced organizations may have no owner; assign one with `TransferOwnershipAction`. Give the integration user `roster.organizations.sync`.

### 8. Single sign-on

- Install the protocol packages you need: `laravel/socialite` plus `firebase/php-jwt` (OIDC), `socialiteproviders/microsoft-azure` (Entra ID) or `socialiteproviders/saml2` (SAML).
- Create connections per organization with `CreateSsoConnectionAction($organization, new CreateSsoConnectionData(name: ..., protocol: 'oidc'|'azure'|'saml', clientId: ..., /* other settings */))`, and register the returned `callback_url` at the provider.
- Link people to `route('roster.sso.discover', ['email' => $email])` or `route('roster.sso.start', $connection->slug)`.
- When an organization enforces SSO, add `new \RefactorCircus\Roster\Domains\Sso\Support\NotSsoEnforced` to the app's password login validation.

### 9. SCIM provisioning

- Issue a token with `CreateScimTokenAction($organization, new CreateScimTokenData(name: 'Okta', ssoConnection: $slug))`; `$issued->plain` is the only copy. The provider's base URL is `url('scim/v2/'.$organization->slug)`.
- The organization must own its email domains (`domains`), or SCIM refuses its users.
- SCIM changes run through Roster's Actions: listen to the normal Action events, and find them in Keen's audit log under surface `scim`.

### 10. Impersonation

- Grant `roster.users.impersonate` explicitly. Start with `StartImpersonationAction($user, new StartImpersonationData(reason: ...), actor: $admin)` and redirect the impersonator's browser to `$started->url`.
- Schedule `roster:end-expired-impersonations` (every five minutes) so sessions the impersonator abandons, and unused links, are ended as `expired` on the record.
- Add `@include('roster::impersonation-banner')` to the app layout (Atrium's standalone banner, styled inline). List app abilities that must never run while impersonating in `roster.impersonation.blocked` (wildcards allowed).
- Use `app(RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext::class)->active()` to detect an impersonated request.

### 11. CSV import and export

- Requires `refactor-circus/impex`: `composer require refactor-circus/impex`, `php artisan vendor:publish --tag="impex-migrations"`, migrate, and run a queue worker and the scheduler. Schedule `roster:prune-transfers`.
- Start with `StartImportAction(new StartImportData(type: TransferType::ImportMembers /* ImportUsers, ImportTeams, ImportOrganizations */, organization: slug, file: $upload /* or content: $csv */), $user)`. It only previews: read `$transfer->rows()` (`action` is create/link/invite/update/skip/error).
- Apply with `ConfirmImportAction($transfer, $user)`. Rows run as that user, with their permissions checked again. Cancel with `CancelTransferAction`.
- Export with `StartExportAction(new StartExportData(type: TransferType::ExportMembers /* ExportUsers, ExportOrganizations */, organization: slug, filters: new ExportFiltersData(externalSource: 'erp')), $user)`, then serve the file through `route('roster.transfers.download', $transfer->id)` or Atrium.
- Get a starting file with `ShowImportTemplateAction(TransferType::ImportMembers)` (or `GET /roster/imports/templates/{type}`); rows starting with `#` are ignored. Customize with `php artisan vendor:publish --tag="roster-import-templates"`.
- Columns: members `email,name,display_name,teams,role`; users `email,name,display_name`; teams `name,slug,members`; organizations `source,external_id,name,account_number,slug,domains,owner`.
- Without Impex, these Actions throw `RefactorCircus\Roster\Domains\Transfer\Exceptions\TransfersUnavailableException`.

### 12. Guard routes and surfaces

- Add `roster.active` middleware to routes only active users may use, and `roster.organization` to routes that need a tenant.
- The HTTP API (`roster.routes.enabled`) and MCP (`roster.mcp.web.enabled`, `roster.mcp.local.enabled`) are off by default and do **no authorization** themselves. Only enable them with auth plus admin-only middleware in the `middleware` config.

### 13. Registered tabs

- Add the app's own tabs to Roster's Atrium organization and user screens from a service provider's `boot()` (never `register()`, middleware or controllers: the registry lives for the whole process):

```php
use RefactorCircus\Roster\Atrium\{TabDefinition, Tabs};

app(Tabs::class)
    ->organization(TabDefinition::make('acme.orders')->label('Orders')->icon('shopping-cart')
        ->view('acme::roster.orders')
        ->resolve(fn (OrganizationModel $organization): array => ['orders' => Order::where('organization_id', $organization->getKey())->get()])
        ->permission('orders.view'))
    ->user(TabDefinition::make('acme.devices')->label('Devices')->component('acme::user-devices')
        ->authorize(fn (Request $request, Model $user): bool => $request->user()?->is($user) === true));
```

- Keys: lowercase letters, digits, `.`, `-`, `_`. Use `vendor.key` (`acme.orders`): new built-in keys may arrive in minor releases, and built-in keys (`Tabs::ORGANIZATION_TABS`, `overview` on users) or a duplicate key throw `InvalidArgumentException`. Each tab needs a `view()` or a `component()`.
- Visibility is default-deny: give a `permission()` (checked in the organization, or for the user, like the built-in tabs), an `authorize(fn (Request, Model $record): bool)` closure (both must pass when both are set), or `public()` (anyone who may open the screen; only for data no more sensitive than the screen). A hidden tab's `?tab=` answers 403.
- `authorize` closures run on every render, for any request method: keep them cheap and non-throwing (a throwing one hides the tab and is reported). The resolver runs only for the open tab, after access checks; its errors, and view errors, surface as page errors. A missing view or component fails only when the tab is opened.
- A view gets the resolver's data under Roster's variables (`organization` / `user`, `tab`, `tabs`, `registeredTab`, `errors`, which always win; don't return those or `__env`, `app`, `loop`); a component gets the record prop and one `data` prop.
- On the user screen the tab bar, with an `overview` tab holding the existing page, appears only while a registered tab is visible. Apps that published `roster-views` must re-publish or merge them to get registered tabs.
- In tests, `app(Tabs::class)->flush()` forgets every registered tab; there is no unregistering at runtime.

## Rules, References, and Templates

- Config: `config/roster.php` (users, authorization, super_admins, roles, impersonation, sso, scim, transfers, organizations, statuses, invitations, routes, mcp).
- Statuses: `roster.statuses` names a string-backed enum per record. Users default to `RefactorCircus\Roster\Domains\User\Enums\UserStatus` (`Active`, `Pending`, `Suspended`, `Deactivated`), implementing `Contracts\AccountStatus` (`active()`, `pending()`, `suspended()`, `deactivated()`, `allowsAccess()`); a user with no profile row has the `active()` status. Organizations and teams default to `OrganizationStatus` / `TeamStatus` (`Active`, `Inactive`), implementing `Contracts\Status`; set them with `status` / `status_reason` on the create, update and sync Data, filter lists by `status`. They are data only: Roster refuses nothing by them. Read the configured enum with `RefactorCircus\Roster\Support\Statuses` (`Statuses::users()::pending()`).
- Deletes are soft:
  - Add `SoftDeletes` (and a `deleted_at` column) to the user model so deleted users can be restored; without it, users are deleted permanently.
  - Restore with `RestoreUserAction` / `RestoreOrganizationAction`.
  - Delete for good with `PurgeUserAction` / `PurgeOrganizationAction` (organizations from the API, MCP and Atrium are purged by a queued `PurgeOrganizationJob`: the API answers 202; `PurgeOrganizationAction::queue()` does the same), or schedule `roster:purge-deleted` (`roster.deletes.retention_days`, null keeps them forever).
- Manual acceptance:
  - Set `roster.users.registration_status` to `pending` for self-registration.
  - Pass `status: UserStatus::Pending` in `CreateUserData`.
  - Set an organization's `provisioned_status` for its SSO and SCIM accounts.
  - Accept with `ApproveUserAction`, or turn down with `RejectUserAction` (which deactivates).
- Events: every Action fires `RefactorCircus\Roster\Domains\{Domain}\Events\*ActionEvent` before (`...ingActionEvent`) and after (`...edActionEvent`); listen to `RefactorCircus\Keystone\Contracts\ActionStartingEvent` / `ActionFinishedEvent` (shared by every Refactor Circus package; Roster's events are under `RefactorCircus\Roster\`) to see every action. Each Action makes its writes in one transaction; finished events fire after it commits, so a write that rolls back (or an outer transaction that does) fires none. CSV steps fire `ImportValidating`/`Validated`, `ImportRowApplying`/`Applied`, `ExportBuilding`/`Built` and `TransferFinishing`/`Finished` action events too.
- Model events: every model fires `{Entity}{Hook}Event` per Eloquent hook (`TeamCreatingEvent`, `OrganizationTrashedEvent`), implementing `RefactorCircus\Keystone\Contracts\ModelLifecycleEvent`; return `false` from a `creating`/`updating`/`saving`/`deleting` listener to stop the write.
- Policies: every model's policy is set in `roster.policies`; swap one by pointing the model at your class (extend the bundled `Domains\{Domain}\Policies\*Policy`). Roster's API, MCP and Atrium checks ask the policy of the permission's resource (`roster.organizations.update` → `OrganizationModel` policy `update(?Model $user, ?Model $subject, ?Model $self)`, `roster.users.manage-status` → `UserModel` policy `manageStatus()`), so app `Gate::before` callbacks apply. Impersonation-blocked permissions are refused first.
- HTTP routes are named `roster.users.*`, `roster.organizations.*`, `roster.invitations.*`; `{user}` is the user model's route key, organizations and teams use slugs.
- Atrium: Users at `atrium.roster.users.index`, Organizations at `atrium.roster.organizations.index`. Access uses Atrium's `viewAtrium` gate. Lists of organizations, roles, impersonations and transfers, asked without an `organization`, open to users holding the permission in any organization and hold only what falls within those (`Authorizer::organizationsWith()`); Atrium's navigation follows. Pages show only the controls the viewer may use and organization tabs need their own permission; published views use `@rosterCan($permission, $scope, $self)`, the same check the screens make. Hide it with `atrium.disabled => ['roster']`, or by feature flag through `roster.atrium.features` (navigation, widgets and search hide and pages 404 while a feature is off; Atrium's feature resolver, e.g. refactor-circus/pennantplus, decides). The default is `RefactorCircus\Roster\Atrium\Features\RosterSupportFeature`, a PennantPlus `OnLayeredFeature` checked globally only; subclass it to change its default, and it is skipped when refactor-circus/pennantplus is not installed.

## Examples

```php
use RefactorCircus\Roster\Domains\User\Actions\{CreateUserAction, SuspendUserAction, UpdateProfileAction};
use RefactorCircus\Roster\Domains\User\Data\{CreateUserData, SuspendUserData, UpdateProfileData};

$user = app(CreateUserAction::class)->execute(new CreateUserData(
    email: 'ada@example.com',
    name: 'Ada Lovelace',
    timezone: 'Europe/London', // profile fields are accepted on create
));

app(UpdateProfileAction::class)->execute($user, new UpdateProfileData(displayName: 'Ada'));

app(SuspendUserAction::class)->execute($user, SuspendUserData::from($request->all()), actor: $request->user());

$user->isRosterActive(); // false
```

```php
use RefactorCircus\Roster\Domains\Invitation\Actions\CreateInvitationAction;
use RefactorCircus\Roster\Domains\Invitation\Data\CreateInvitationData;
use RefactorCircus\Roster\Domains\Organization\Actions\CreateOrganizationAction;
use RefactorCircus\Roster\Domains\Organization\Data\CreateOrganizationData;

$acme = app(CreateOrganizationAction::class)->execute(new CreateOrganizationData(name: 'Acme', domains: ['acme.com']), owner: $request->user());

app(CreateInvitationAction::class)->execute($acme, new CreateInvitationData(email: 'ada@example.com'), actor: $request->user());

$request->user()->currentOrganization()?->slug; // 'acme'
```

```php
Route::middleware(['auth', 'roster.active', 'roster.organization'])->group(function () {
    // only active users who belong to an organization
});
```

In app tests, assert with `expect($user->rosterStatus())->toBe(UserStatus::Suspended)`, `Event::fake([UserSuspendedActionEvent::class])`, or `Notification::fake()` + `Notification::assertSentOnDemand(RefactorCircus\Roster\Domains\Invitation\Notifications\InvitationNotification::class)`.

## Anti-patterns

- Adding profile or status columns to the users table: Roster keeps them in `roster_profiles`.
- Writing `roster_profiles` rows or status fields directly instead of calling the Actions, which skips the guards and events.
- Enabling `roster.routes` or `roster.mcp.web` without authentication middleware; permission checks need a signed-in user.
- Setting `roster.authorization` to false in production.
- Running Cortex agents with no signed-in user and expecting Roster tools to work; they are refused.
- Expecting an audit trail without refactor-circus/keen installed; Roster records nothing on its own.
- Putting secrets in `Keen::record()->with()` without listing their keys in `keen.redact`.
- Styling Roster's views with `<style>`, `style=` or classes outside Atrium's safelist; publish and use Atrium components instead.
- Publishing Roster's views to add a tab to the organization or user screen; register it on `Tabs` instead.
- Logging users in as someone else with `Auth::login()` for support. Use impersonation, which is time-limited, blocked from sensitive abilities, and audited.
- Emailing or posting impersonation links. They only work in the impersonator's own browser.
- Using multi-tenant Entra authorities (`common`, `organizations`) or the Entra `mail` attribute for identity. Roster refuses both.
- Writing your own OIDC/SAML verification. Use Roster's connections, which verify tokens and assertions with proven libraries.
- Storing or logging SCIM tokens. They are shown once; issue a new one and revoke the old if lost.
- Checking roles by slug in app code (`hasRole('admin')`-style). Check permissions with `$user->can()` instead.
- Writing `roster_role_assignments` rows directly, which skips the scope and escalation guards.
- Changing `roster.users.key_type` after `roster_profiles` has been migrated.
- Putting a `password` column in an import CSV (it is refused), or serving `roster.transfers.disk` files publicly instead of through Roster's download route.
- Calling a user model's `profile()` relation for Roster data: the relation is `rosterProfile` (and `rosterMemberships` / `rosterOrganizations`).
- Storing ERP/CRM ids in your own columns or writing `roster_organization_links` directly; use the sync and link Actions, which enforce one record per source and audit every change.
- Writing `roster_memberships` or `roster_team_members` rows directly; seats must go through an organization membership.
- Removing `auth` from `roster.invitations.middleware`: accepting acts as the signed-in user.
- Enabling domain auto-join and expecting unverified users to join; that is deliberately blocked.
