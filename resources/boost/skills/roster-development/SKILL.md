---
name: roster-development
description: >
  Configure and apply the Roster package in Laravel applications: user CRUD,
  profiles, suspend/deactivate/reactivate, organizations, teams, invitations,
  domain auto-join, current org/team context, roles and permissions through
  Laravel's Gate, audit history (through refactor-circus/keen), impersonation, CSV import/export,
  the roster.active and roster.organization middleware, Roster's HTTP
  API, MCP server and Atrium screens, and extending Roster from another
  package (resources, permissions, membership sources, sign-in requirements).
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

- The Atrium dashboard is optional: `composer require refactor-circus/atrium` (v0.1.0 or later) for Roster's admin screens. The Actions, HTTP API, MCP server, invitation page and impersonation banner work without it.
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

- Roster has no audit log or audit code of its own. Install refactor-circus/keen (`composer require refactor-circus/keen`, `php artisan migrate`) and Keen records every Roster change, labelled, diffed (profile fields, role permissions, organization domains), scoped to its organization, and with the impersonator or import in its context. Without Keen there is no history anywhere in Roster.
- Turn it off with `roster.audit.enabled => false` (Keen then records nothing of Roster's). `Roster::auditing()` says whether Roster audits now.
- While it does, read history in Keen (which adds it to Roster's Atrium screens through Atrium's screen extensions; Roster renders none itself), or through `GET /roster/history` and `list-roster-history-tool` (global `roster.audit.view`). Roster defines the `viewAuditLog` Gate ability as global `roster.audit.view` unless the app defines it.
- Record the app's own events with Keen: `Keen::record('invoice.paid')->on($model)->in($organization)->with([...])->save();`. Add app secrets to `keen.redact`.

### 7. Organizations from external systems

- Sync an ERP or CRM record with `SyncOrganizationAction(new SyncOrganizationData(source: 'erp', externalId: $id, accountNumber: ..., name: ..., domains: [...]))`. It creates or updates by `source` + `external_id`, writes only the fields given, and returns `->outcome` (created/updated/unchanged) and `->organization`.
- Use `SyncOrganizationsAction(new SyncOrganizationsData(records: [...]))` for scheduled batches (each record an array, validated on its own); it returns per-record results and never fails the whole batch. Use the `import_organizations` CSV type for files; `export_organizations` writes the same columns (`filters.external_source` for one system), so exports re-import unchanged.
- Find organizations with `ListOrganizationsAction(new ListOrganizationsData(source: 'erp', accountNumber: 'A-42'))`. Each organization's `links` holds `source`, `external_id`, `account_number` and `synced_at`.
- Synced organizations may have no owner; assign one with `TransferOwnershipAction`. Give the integration user `roster.organizations.sync`.

### 8. Single sign-on and provisioning

- Roster ships neither: install refactor-circus/guardian (`composer require refactor-circus/guardian`), which adds OpenID Connect, SAML 2.0 and Entra ID sign-in, its enforcement and provisioning to Roster's organizations through Roster's seams.
- **Without Guardian, organizations that enforce single sign-on accept password sign-in.** Roster adds no guard of its own, so install Guardian in the same deploy as the Roster upgrade that dropped them.
- Upgrading from a Roster that still had them: before any migrate command, delete the app's published copies of Roster's old sign-on and provisioning table migrations (never roll them back: their `down()` drops the tables) and strip those keys from published copies of the two index migrations; then publish and run Guardian's migrations before `php artisan roster:sync-permissions`. If Guardian was installed before that Roster upgrade, run `php artisan guardian:upgrade-from-roster` after it to finish moving the tables and role grants. Roster's CHANGELOG has every step.
- Add `new \RefactorCircus\Roster\Support\Rules\NoExternalSignIn` to the app's password login validation: it fails for any email a registered sign-in requirement claims (Guardian's, or your own registered with `Roster::requireSignIn()`).

### 9. Impersonation

- Grant `roster.users.impersonate` explicitly. Start with `StartImpersonationAction($user, new StartImpersonationData(reason: ...), actor: $admin)` and redirect the impersonator's browser to `$started->url`.
- Schedule `roster:end-expired-impersonations` (every five minutes) so sessions the impersonator abandons, and unused links, are ended as `expired` on the record.
- Add `@include('roster::impersonation-banner')` to the app layout (Atrium's standalone banner when Atrium is installed, a plain one otherwise; both styled inline). List app abilities that must never run while impersonating in `roster.impersonation.blocked` (wildcards allowed).
- Use `app(RefactorCircus\Roster\Domains\Impersonation\Services\ImpersonationContext::class)->active()` to detect an impersonated request.

### 10. CSV import and export

- Requires `refactor-circus/impex`: `composer require refactor-circus/impex`, `php artisan vendor:publish --tag="impex-migrations"`, migrate, and run a queue worker and the scheduler. Schedule `roster:prune-transfers`.
- Start with `StartImportAction(new StartImportData(type: TransferType::ImportMembers /* ImportUsers, ImportTeams, ImportOrganizations */, organization: slug, file: $upload /* or content: $csv */), $user)`. It only previews: read `$transfer->rows()` (`action` is create/link/invite/update/skip/error).
- Apply with `ConfirmImportAction($transfer, $user)`. Rows run as that user, with their permissions checked again. Cancel with `CancelTransferAction`.
- Export with `StartExportAction(new StartExportData(type: TransferType::ExportMembers /* ExportUsers, ExportOrganizations */, organization: slug, filters: new ExportFiltersData(externalSource: 'erp')), $user)`, then serve the file through `route('roster.transfers.download', $transfer->id)` or Atrium.
- Get a starting file with `ShowImportTemplateAction(TransferType::ImportMembers)` (or `GET /roster/imports/templates/{type}`); rows starting with `#` are ignored. Customize with `php artisan vendor:publish --tag="roster-import-templates"`.
- Columns: members `email,name,display_name,teams,role`; users `email,name,display_name`; teams `name,slug,members`; organizations `source,external_id,name,account_number,slug,domains,owner`.
- Without Impex, these Actions throw `RefactorCircus\Roster\Domains\Transfer\Exceptions\TransfersUnavailableException`.

### 11. Guard routes and surfaces

- Add `roster.active` middleware to routes only active users may use, and `roster.organization` to routes that need a tenant.
- The HTTP API (`roster.routes.enabled`) and MCP (`roster.mcp.web.enabled`, `roster.mcp.local.enabled`) are off by default and do **no authorization** themselves. Only enable them with auth plus admin-only middleware in the `middleware` config.

### 12. Screen extensions

- Atrium is optional (`composer require refactor-circus/atrium` for the screens). Every Roster dashboard screen declares itself with `Screen::standard()` on Atrium's screen extensions registry; add tabs and sections to it with `Atrium::screens()` from a service provider's `boot()` (never `register()`, middleware or controllers: the registry lives for the whole process), behind `class_exists(Atrium::class)`. Atrium's README ("Screen extensions") has the full rules.

```php
use RefactorCircus\Atrium\Domains\Screens\Data\{Screen, SectionDefinition, TabDefinition};
use RefactorCircus\Atrium\Facades\Atrium;

Atrium::screens()
    ->tab('roster.organization', TabDefinition::make('acme.orders')->label('Orders')->icon('shopping-cart')
        ->view('acme::roster.orders')
        ->permission('orders.view')
        ->resolve(fn (?Model $organization, Screen $screen): array => ['orders' => Order::where('organization_id', $organization?->getKey())->get()]))
    ->section('roster.user', 'aside', SectionDefinition::make('acme.devices')->component('acme::user-devices')
        ->authorize(fn (Request $request, ?Model $user, Screen $screen): bool => $request->user()?->is($user) === true));
```

- Screen keys (list screens have the slots `header.actions`, `before`, `after` and the row slot `row.actions`; detail screens `header.actions`, `before`, `after` and `aside`):
  - list: `roster.users`, `roster.organizations`, `roster.roles`, `roster.permissions`, `roster.impersonations`, `roster.transfers` (no record; each row is the row slot's `$record`)
  - detail without a record: `roster.user-create`, `roster.organization-create`
  - detail with a record: `roster.user` (the user), `roster.organization` (the organization), `roster.team` (the team), `roster.role` (the role), `roster.transfer` (the transfer), `roster.invitation` (the invitation page)
- Built-in tabs: `overview` on every screen but the organization (the tab bar shows only once a registered tab is visible); on `roster.organization`, `members`, `teams`, `invitations`, `roles`, `settings`, each shown only with its permission in the organization, the first shown being the default. All five are reserved for every viewer: a registered tab with one of those keys is skipped and reported. Use `vendor.key` (`acme.orders`) keys.
- `permission()` extensions are checked by `ScreenAccess::allowsOn($permission, $record)`: `OrganizationModel` / `TeamModel` in that scope; the configured `roster.users.model` about that user; `RoleModel`, `ImpersonationModel` and `InvitationModel` in `$record->organization` (globally without one); `TransferModel` in `$record->organization` about `$record->requester`; no record (list and create screens) without a scope; any other model is refused.
- Visibility is default-deny: `permission()`, an `authorize(fn (Request, ?Model, Screen): bool)` closure (both must pass when both are set), or `public()`. A hidden tab's `?tab=` answers 403; an unknown key, including the old `activity` and `mcp`, opens the default tab. Roster authorizes the screen first, so your closures never run for a refused viewer or on deleted pages.
- A view gets the resolver's data with Atrium's `screen`, `record` and `tab` over it; a component gets one `data` prop. In tests use `RefactorCircus\Atrium\Testing\ResetsScreenExtensions`. Apps that published `roster-views` must re-publish them.

### 13. Extending Roster

- Packages and apps add to Roster through registration points, always from a service provider's `boot()` (each registry is a process-wide singleton, and registering the same thing twice throws).
- `roster.*` is reserved for Roster's own domains: `resource()` and `permission()` throw `InvalidArgumentException` for any `roster.*` prefix or name. Use your package's prefix (`acme.widgets`).
- Resources and permissions:

```php
use Illuminate\Support\Facades\Gate;
use RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue;
use RefactorCircus\Roster\Domains\Permission\Services\Resources;

app(Resources::class)->resource('acme.widgets', WidgetModel::class);
Gate::policy(WidgetModel::class, WidgetPolicy::class); // WidgetPolicy::approve() answers acme.widgets.approve

app(PermissionCatalogue::class)
    ->permission('acme.widgets.view', 'See widgets.', ['admin', 'member'])
    ->permission('acme.widgets.approve', 'Approve widgets.', ['admin']);
```

- `{prefix}.{verb}` asks the registered model's policy by the camel-cased verb, in `Authorizer::check()`, API/MCP requests, `ScreenAccess` and `@rosterCan`. Extend `RefactorCircus\Roster\Support\Policies\Policy` and answer with `$this->permits(...)`. A prefix is two lower-case segments (`^[a-z0-9-]+\.[a-z0-9-]+$`); an unregistered prefix is refused; a registered model with no policy throws `InvalidArgumentException` naming the resource on first use. Records find their organization/team through `organization_id`/`team_id` or `organization()`/`team()`.
- Catalogued permissions are created as system permissions by the role migration, `BuiltInRoles::sync()` and `php artisan roster:sync-permissions`. Run `roster:sync-permissions` when a package registers after the migration ran. Existing built-in roles never gain a newly registered permission automatically: grants apply only to built-in roles the sync creates, so grant it to existing roles by hand.
- The impersonation-blocked list stays core-only: registered permissions are never added to `roster.impersonation.blocked`; add your own patterns there if they must not run while impersonating.
- Membership sources:

```php
use RefactorCircus\Roster\Domains\Organization\Services\MembershipSources;

app(MembershipSources::class)->register('hris', 'HR system');
```

- `$membership->source` is a string; `$membership->sourceEnum()` gives the built-in `MembershipSource` case or null (never compare `->source` with an enum case). Members show the registered label; an unregistered stored source shows raw.
- Sign-in requirements and provisioning status:

```php
use RefactorCircus\Roster\Contracts\SignInRequirement;
use RefactorCircus\Roster\Data\ExternalSignIn;
use RefactorCircus\Roster\Facades\Roster;

final class PartnerSignIn implements SignInRequirement
{
    public function for(string $email): ?ExternalSignIn
    {
        return str_ends_with(strtolower($email), '@partner.example')
            ? new ExternalSignIn(key: 'partner', label: 'Partner Inc.', url: route('partner.sign-in'))
            : null;
    }
}

Roster::requireSignIn(PartnerSignIn::class);          // in boot()
Roster::externalSignInFor($email)?->url;              // first answer, in registration order; null for super-admins
Roster::provisionedStatus($organization);             // the AccountStatus externally provisioned accounts start with
```

- Requirements must not overlap. A throwing requirement fails closed: the login or registration (`NoExternalSignIn`) fails and Laravel reports it. Ask whether an email must sign in elsewhere with `externalSignInFor()` (its `record` is whatever a registered sign-in requirement keeps for it). With no requirement registered, every email passes `NoExternalSignIn`.
- Audit context is not a Roster API: register on Keystone's `AuditHooks`, filter to Roster's entries and never throw (`AuditHooks` runs every closure for every package and spreads each result, so return `[]`, never null):

```php
use RefactorCircus\Keystone\Audit\AuditHooks;
use RefactorCircus\Keystone\Packages\PackageRegistry;

$this->app->booted(function (): void {
    if (! \RefactorCircus\Roster\Facades\Roster::auditing()) {
        return;
    }

    app(AuditHooks::class)->context(function (object $event): array {
        try {
            if (app(PackageRegistry::class)->for($event)?->key !== 'roster') {
                return [];
            }

            return ['acme_device' => app(DeviceContext::class)->summary()];
        } catch (Throwable $e) {
            report($e);

            return [];
        }
    });
});
```

## Rules, References, and Templates

- Config: `config/roster.php` (users, authorization, super_admins, roles, impersonation, transfers, organizations, statuses, invitations, routes, mcp).
- Statuses: `roster.statuses` names a string-backed enum per record. Users default to `RefactorCircus\Roster\Domains\User\Enums\UserStatus` (`Active`, `Pending`, `Suspended`, `Deactivated`), implementing `Contracts\AccountStatus` (`active()`, `pending()`, `suspended()`, `deactivated()`, `allowsAccess()`); a user with no profile row has the `active()` status. Organizations and teams default to `OrganizationStatus` / `TeamStatus` (`Active`, `Inactive`), implementing `Contracts\Status`; set them with `status` / `status_reason` on the create, update and sync Data, filter lists by `status`. They are data only: Roster refuses nothing by them. Read the configured enum with `RefactorCircus\Roster\Support\Statuses` (`Statuses::users()::pending()`).
- Deletes are soft:
  - Add `SoftDeletes` (and a `deleted_at` column) to the user model so deleted users can be restored; without it, users are deleted permanently.
  - Restore with `RestoreUserAction` / `RestoreOrganizationAction`.
  - Delete for good with `PurgeUserAction` / `PurgeOrganizationAction` (organizations from the API, MCP and Atrium are purged by a queued `PurgeOrganizationJob`: the API answers 202; `PurgeOrganizationAction::queue()` does the same), or schedule `roster:purge-deleted` (`roster.deletes.retention_days`, null keeps them forever).
- Manual acceptance:
  - Set `roster.users.registration_status` to `pending` for self-registration.
  - Pass `status: UserStatus::Pending` in `CreateUserData`.
  - Set an organization's `provisioned_status` for its externally provisioned accounts.
  - Accept with `ApproveUserAction`, or turn down with `RejectUserAction` (which deactivates).
- Events: every Action fires `RefactorCircus\Roster\Domains\{Domain}\Events\*ActionEvent` before (`...ingActionEvent`) and after (`...edActionEvent`); listen to `RefactorCircus\Keystone\Contracts\ActionStartingEvent` / `ActionFinishedEvent` (shared by every Refactor Circus package; Roster's events are under `RefactorCircus\Roster\`) to see every action. Each Action makes its writes in one transaction; finished events fire after it commits, so a write that rolls back (or an outer transaction that does) fires none. CSV steps fire `ImportValidating`/`Validated`, `ImportRowApplying`/`Applied`, `ExportBuilding`/`Built` and `TransferFinishing`/`Finished` action events too.
- Model events: every model fires `{Entity}{Hook}Event` per Eloquent hook (`TeamCreatingEvent`, `OrganizationTrashedEvent`), implementing `RefactorCircus\Keystone\Contracts\ModelLifecycleEvent`; return `false` from a `creating`/`updating`/`saving`/`deleting` listener to stop the write.
- Policies: every core model's policy is set in `roster.policies` (package resources register theirs with the Gate themselves; a `roster.policies` entry for one of their models still wins); swap one by pointing the model at your class (extend the bundled `Domains\{Domain}\Policies\*Policy`). Roster's API, MCP and Atrium checks ask the policy of the permission's resource (`roster.organizations.update` → `OrganizationModel` policy `update(?Model $user, ?Model $subject, ?Model $self)`, `roster.users.manage-status` → `UserModel` policy `manageStatus()`), so app `Gate::before` callbacks apply. Impersonation-blocked permissions are refused first.
- HTTP routes are named `roster.users.*`, `roster.organizations.*`, `roster.invitations.*`; `{user}` is the user model's route key, organizations and teams use slugs.
- Atrium (optional; without it the `atrium.roster.*` routes don't exist): Users at `atrium.roster.users.index`, Organizations at `atrium.roster.organizations.index`. Access uses Atrium's `viewAtrium` gate. Lists of organizations, roles, impersonations and transfers, asked without an `organization`, open to users holding the permission in any organization and hold only what falls within those (`Authorizer::organizationsWith()`); Atrium's navigation follows. Pages show only the controls the viewer may use and organization tabs need their own permission; published views use `@rosterCan($permission, $scope, $self)`, the same check the screens make. Hide it with `atrium.disabled => ['roster']`, or by feature flag through `roster.atrium.features` (navigation, widgets and search hide and pages 404 while a feature is off; Atrium's feature resolver, e.g. refactor-circus/pennantplus, decides). The default is `RefactorCircus\Roster\Atrium\Features\RosterSupportFeature`, a PennantPlus `OnLayeredFeature` checked globally only; subclass it to change its default, and it is skipped when refactor-circus/pennantplus is not installed.

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
- Publishing Roster's views to add a tab or section to a Roster screen; register it with `Atrium::screens()` instead.
- Logging users in as someone else with `Auth::login()` for support. Use impersonation, which is time-limited, blocked from sensitive abilities, and audited.
- Emailing or posting impersonation links. They only work in the impersonator's own browser.
- Checking roles by slug in app code (`hasRole('admin')`-style). Check permissions with `$user->can()` instead.
- Writing `roster_role_assignments` rows directly, which skips the scope and escalation guards.
- Changing `roster.users.key_type` after `roster_profiles` has been migrated.
- Putting a `password` column in an import CSV (it is refused), or serving `roster.transfers.disk` files publicly instead of through Roster's download route.
- Calling a user model's `profile()` relation for Roster data: the relation is `rosterProfile` (and `rosterMemberships` / `rosterOrganizations`).
- Storing ERP/CRM ids in your own columns or writing `roster_organization_links` directly; use the sync and link Actions, which enforce one record per source and audit every change.
- Writing `roster_memberships` or `roster_team_members` rows directly; seats must go through an organization membership.
- Removing `auth` from `roster.invitations.middleware`: accepting acts as the signed-in user.
- Enabling domain auto-join and expecting unverified users to join; that is deliberately blocked.
- Upgrading Roster past the sign-on and provisioning removal without installing refactor-circus/guardian in the same deploy, or rolling back the old published table migrations Guardian takes over.
