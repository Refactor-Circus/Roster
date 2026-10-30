# Build

## Group 1

Authorization resources and permission catalogue (criteria 1, 2, 3). Task 1.1 is done. Task 1.2 is done except one item, which waits on a decision (below), so 1.2 and 1.3 stay unticked.

### Files

- `src/Domains/Permission/Services/Resources.php` (new): a singleton registry from prefix to model with its own `generation()`. Public `resource()` rejects `roster.*`. `rosterResource()` is `@internal` and accepts only `roster.*`. `reserved()`, `model()` and `all()` are read methods. Prefixes must match `^[a-z0-9-]+\.[a-z0-9-]+$`; duplicates and non-model classes throw `InvalidArgumentException` naming the prefix.
- `src/Domains/Permission/Services/PermissionCatalogue.php` (new): a singleton. Public `permission()` rejects `roster.*`; `rosterPermission()` is `@internal`. Registering a name twice (including a core `BuiltInRoles::PERMISSIONS` name), a reserved name, or a role slug that is not built in throws, naming the value. Also has `has()`, `all()` and `grantedTo()`.
- `src/Domains/Permission/Services/Authorizer.php`:
  - `RESOURCES` keeps only the core six, and there are no SSO/SCIM imports.
  - `resolvePolicy()` looks up everything before the last dot in `Resources`. A package's registered model with no Gate policy throws `InvalidArgumentException` naming the resource. Nothing is memoised for a check that throws.
  - The memo and the resolved policies start over when `Resources::generation()` changes, as well as on `Permissions::generation()`.
  - `parentOf()` falls back to the `organization_id` / `team_id` attribute for models without the relation.
- `src/Domains/Permission/PermissionServiceProvider.php`: binds `Resources` (the core six registered as `roster.{resource}` in the singleton's factory in `register()`) and `PermissionCatalogue` as singletons.
- `src/Domains/Role/Support/BuiltInRoles.php`:
  - `PERMISSIONS` drops `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage`.
  - `sync()` merges the catalogue and grants registered permissions only to the roles it creates.
  - `sync()` now returns the system permissions nothing registers (left in place).
- `src/Domains/Permission/Console/Commands/SyncPermissionsCommand.php`: warns once per unregistered system permission.
- `src/Domains/Sso/SsoServiceProvider.php` and `src/Domains/Scim/ScimServiceProvider.php`: register `roster.sso` / `roster.scim` and their permissions (with `admin` grants) through the `@internal` methods, and their models' policies through Gate `policy()`. All of this happens in `boot()`, before SSO's availability check.
- Tests (new):
  - `tests/Unit/Permission/ResourcesTest.php`
  - `tests/Unit/Permission/PermissionCatalogueTest.php`
  - `tests/Feature/Permission/RegisteredResourcesTest.php`
  - `tests/Feature/Permission/PermissionSyncTest.php`

### Tests

- The four new files: 46 tests, all passing.
- `tests/Authorization`, `tests/ArchTest.php`, `tests/Feature/Sso`, `tests/Feature/Scim` and `tests/Feature/DocsTest.php`, unchanged: 596 tests, all passing.
- `phpstan` on the changed source and `pint --test` on every changed file both pass.
- The full suite was not run here; the gate runs it.

### Deviations

- `config/roster.php` `policies` still lists the five SSO/SCIM models. Removing them fails two existing tests that criterion 10 does not allow changing:
  - `tests/ArchTest.php` "every model has a policy in roster.policies" requires the config file itself to list every domain model.
  - `tests/Authorization/PoliciesTest.php` "registers a configurable policy for every model" compares `config('roster.policies')` keys with every model.

  This is returned as a decision. The SSO/SCIM providers already register the policies themselves, so removing the entries later is a one-file change.
- A missing policy throws only for a package's prefix, not for Roster's own `roster.*` resources. Those keep today's documented fallback: README and `config/roster.php` say "remove a model's entry to check its resource by Roster's permissions alone". The examples and tests in criterion 1 all use a package's resource.
- An app that removed an SSO/SCIM model from its published `roster.policies` now gets the bundled policy back from the domain provider. An app that points the model at its own class still wins, because the package provider registers config policies after the domain providers boot.

## Group 1 (run 2)

This run finishes task 1.2 under D-020 and keeps the D-021 behaviour as it is. Tasks 1.2 and 1.3 are ticked.

### Files

- `config/roster.php`: the five SSO/SCIM entries (`SsoConnectionModel`, `SsoIdentityModel`, `ScimTokenModel`, `ScimUserModel`, `ScimGroupModel`) and their imports are gone from `policies`, which now lists only core models. The comment no longer names `sso` / `scim` as resources. It says the SSO and SCIM domains register their own policies, and that an app entry for one of those models still wins.
- `tests/ArchTest.php` "every model has a policy in roster.policies": the input to `modelsWithoutPolicies()` is now the config file's `policies` plus the Gate's policies after boot. `bootedGatePolicies()` gets those by running a `TestCase` through `setUp()` and `tearDown()`, so error handlers and the app are cleaned up afterwards.
- `tests/Authorization/PoliciesTest.php` "registers a configurable policy for every model": compares `config('roster.policies')` plus the Gate-registered `RefactorCircus\Roster\` policies with every model. The `getPolicyFor()` instance check is unchanged.
- D-021 needed no code change: `Authorizer::resolvePolicy()` already throws only for package prefixes, and `roster.*` falls back to `permits()`.

### Tests

- Run in the worktree: `tests/Authorization`, `tests/Feature/Sso`, `tests/Feature/Scim`, `tests/Feature/Permission`, `tests/Unit/Permission`, `tests/Modes/SsoUnavailable`, `tests/Feature/DocsTest.php` and `tests/ArchTest.php`. 644 tests, all passing. The existing SSO/SCIM authorization tests are unchanged.
- `pint --test` passes on the three changed files. The gate runs the full suite.

### Deviations

- None from the standards. Two existing tests changed, both allowed by D-020: `tests/ArchTest.php` "every model has a policy in roster.policies" and `tests/Authorization/PoliciesTest.php` "registers a configurable policy for every model".
- Left for group 5, because the README and the Boost skill are not this group's files:
  - README "Policies" (line 272) still says every model's policy is registered from `roster.policies`.
  - `resources/boost/skills/roster-development/SKILL.md` line 159 says the same.

## Group 2

Membership sources (criterion 4). Tasks 2.1 and 2.2 are done and ticked. Task 2.3 stays unticked: one existing test outside criterion 10's list fails because of the string cast, and that is returned as a decision (below).

### Files

- `src/Domains/Organization/Services/MembershipSources.php` (new). A singleton holding the five built-in sources (the enum's cases) and the registered ones.
  - `register($source, $label)` throws `InvalidArgumentException` naming the value for a built-in source, a duplicate, or a value the `source` column (32 characters) cannot hold.
  - `has()` answers true for built-in and registered sources.
  - `label()` gives the registered label, or the value itself for a built-in or unregistered source.
  - `all()` lists every source with its label.
- `src/Domains/Organization/Enums/MembershipSource.php`: `Scim` is gone.
- `src/Domains/Organization/Models/MembershipModel.php`:
  - `source` is cast to `string`, and `sourceEnum(): ?MembershipSource` uses `tryFrom()`.
  - A set-only `source()` attribute stores a `MembershipSource` case as its value. Without it, `MembershipFactory` (which keeps passing `MembershipSource::Direct`, as the spec says) would leave an enum in the attributes, and the string cast would fail on it when read.
- `src/Domains/Organization/Concerns/ManagesMemberships.php`: `join()` takes `MembershipSource|string`. If `MembershipSources::has()` says no, it throws `InvalidArgumentException` naming the source, before any write.
- `src/Domains/Organization/Resources/MemberResource.php`: `source` is `MembershipSources::label()`, and the schema declares `source` as a plain required string (with a description, no enum).
- `src/Domains/Organization/OrganizationServiceProvider.php`: `register()` binds `MembershipSources` as a singleton.
- `src/Domains/Scim/ScimServiceProvider.php`: `boot()` registers `scim` / "SCIM".
- `src/Domains/Scim/Services/ScimUsers.php`: both `join()` calls pass `'scim'`, and the enum import is gone.
- Outside the group's file list, both forced by the string cast (each read `->source->value`, which now fails on a string):
  - `src/Domains/Transfer/Services/Exporters/MembersExporter.php`: the export's `source` cell is `$membership->source`. The output is the same stored value as before.
  - `resources/views/ui/organizations/show.blade.php`: the members table cell is `{{ $membership->source }}`. The output is unchanged: the raw stored value, not the label, since the spec says no screen changes.
- `CHANGELOG.md`: a Breaking entry with an upgrade note. It names `sourceEnum()` as the migration path, warns that `=== MembershipSource::X` now silently evaluates false and that `match` on `->source` throws `UnhandledMatchError`, and says an unregistered stored value displays raw. It also notes that SCIM members now show `SCIM` in the API/MCP member data.
- Tests (new):
  - `tests/Unit/Organization/MembershipSourcesTest.php`
  - `tests/Feature/Organization/MembershipSourceTest.php`. This includes the consumer grep test. It scans `src/` and `resources/views` for:
    - `->source` compared (`==`, `===`, `!=`, `!==`, either side) with a `MembershipSource` case
    - `match` on `->source`
    - a property or method read from `->source`
    - a case passed to `where`/`whereIn`/`whereNot`/`whereNotIn('source', ...)`

    A dataset proves each pattern catches a sample line.
- Existing tests changed, as criterion 10 allows: `->source` becomes `->sourceEnum()`, with the same expected case.
  - `tests/Feature/ContextAndDomainTest.php`: "joins auto-join organizations on email verification" (line 77) and "creates a personal organization when configured" (line 93)
  - `tests/Feature/InvitationActionsTest.php`: "accepts an invitation into the organization and its teams" (line 69)
  - `tests/Feature/OrganizationActionsTest.php` "creates an organization with its owner as first member"

### Consumer audit (for the PR)

Every `->source` read and `MembershipSource` use under `src/` and `resources/`:

- Fixed because they read the enum:
  - `MemberResource::toArray()`
  - `MembersExporter` line 61
  - `ui/organizations/show.blade.php` line 78
- Pass a case to `join()`, and still work:
  - `AddMemberAction`
  - `CreateOrganizationAction`
  - `JoinOrganizationsByDomainAction`
  - `SyncOrganizationAction`
  - `AcceptInvitationAction`
  - `SsoLoginAction`
  - `MembersPlanner`
  - `MembershipFactory` (in `database/`)
- `ScimUsers` passes `'scim'`.
- The other `source` hits are organization links (`OrganizationLinkModel::$source`, a string already), sync/import records, or exporter headers. None of them is a membership source.

### Tests

- Run in the worktree on the two new files: 38 tests, all passing.
- Also run:
  - the three changed tests
  - `tests/Feature/Scim` and `tests/Feature/Transfers`
  - `tests/Feature/Http/OrganizationsHttpTest.php`, which asserts `data.source` is `direct` and is unchanged
  - `tests/Feature/Mcp/OrganizationToolsTest.php`
  - `tests/Feature/Ui/OrganizationPagesTest.php`

  137 tests, 136 passing. The failure is `tests/Feature/Scim/ScimUsersTest.php` "provisions a new member from Okta", line 43: `expect($this->acme->membershipFor($user)?->source->value)->toBe('scim')` now fails with `Attempt to read property "value" on string`. That test is not in criterion 10's list, so it is left unchanged and returned as a decision.
- `phpstan` on `src/Domains/Organization`, `src/Domains/Scim` and `MembersExporter` and `pint --test` on every changed PHP file both pass.

### Deviations

- None from the standards.
- The member label for built-in sources is their stored value (`direct`, ...), not a title-cased label. `tests/Feature/Http/OrganizationsHttpTest.php` asserts `data.source` is `direct`, and criterion 10 does not allow changing it. Only registered sources show a different label (`scim` shows `SCIM`).
- Two source files outside the group's list changed (`MembersExporter`, `show.blade.php`), listed above.
- `register()` also rejects an empty value or one longer than the 32-character column. The spec does not ask for this; it stops a registration that could never be stored.

## Group 3

SCIM audit context on Keystone `AuditHooks` (criteria 5, 10). Tasks 3.1, 3.2 and 3.3 are ticked; the gate runs the suite.

### Files

- `tests/Feature/Scim/ScimUsersTest.php` "provisions a new member from Okta" (line 43): `?->source->value` becomes `?->source`, on the same `'scim'` assertion (D-022).
- `src/Support/Audit.php`: `context()` keeps `impersonator` and `transfer` only. The `scim_token` block and the `ScimContext` import are gone, and the docblocks no longer mention the SCIM token.
- `src/Domains/Scim/ScimServiceProvider.php`: `boot()` calls a private `registerAuditContext()`. It adds an `app->booted()` callback, registered at the same moment as `Support\Audit::register()`, that does nothing unless `Roster::auditing()`. When Roster audits, it registers a closure on `AuditHooks::context()`. The closure:
  - returns `[]` unless `PackageRegistry::for($event)?->key === 'roster'`
  - returns `['scim_token' => ['id' => ..., 'name' => ...]]` while `ScimContext` holds a token (shape unchanged)
  - wraps all of this in try/catch on `Throwable`; on a throw it calls `report($e)` and returns `[]`
  No Keystone change.
- `tests/Feature/Scim/ScimAuditContextTest.php` (new, at the listed path). It needs Keen installed. `tests/Pest.php` already gives every file under `tests/Feature` the base `TestCase`, and Pest throws `TestCaseAlreadyInUse` for a second test case. So the file declares a small trait, `InstallsKeenForScimAudit`, and adds it with `uses()`. The trait appends `KeenServiceProvider` to `getPackageProviders()`, which is exactly what `KeenTestCase` does. The database schema is the same, since Keen's migrations are in every test case's paths. The retry moved the file here from `tests/Modes/Keen/ScimAuditContextTest.php`, which the first run had used. Its tests:
  - a SCIM `POST /Users` records `scim_token` with `{id, name}`
  - during a SCIM context, the entry for a billing (non-Roster) event has no `scim_token`, and a Roster entry in the same context does
  - with a `ScimContext` binding that throws, the `user.suspended` entry is still written without `scim_token`, and the exception is reported (`Exceptions::fake()` / `assertReported`)
  - dataset: booting the SCIM provider again on fresh `AuditHooks` with `roster.audit.enabled` on registers the context, and with it off registers nothing (`contextFor()` answers `[]` for a Roster event while a token is set)

### Tests

- The existing `tests/Modes/Keen/KeenAuditTest.php` "records SCIM changes with the token that made them, through the scim surface" is unchanged and still applies.
- `pint --test` passes on the changed files, and `phpstan` passes on the two changed source files.
- Only targeted runs, not the suite (the gate runs it). `tests/Feature/Scim` passed 41 tests, the new file included, and `tests/Modes/Keen` passed before the file moved.

### Deviations

- The "nothing registered when off" case can't change config before the test application boots. Instead it sets the config, swaps in a fresh `AuditHooks` and fresh registries, and boots a new `ScimServiceProvider`. On an application that has already booted, its `booted()` callback runs at once.
- Left for group 5, because the file is not this group's: the Roster `CLAUDE.md` still says `Support\Audit` registers "the impersonator/transfer/SCIM token context".

## Group 4

Sign-in requirements and provisioned status (criteria 6, 7). Tasks 4.1, 4.2 and 4.3 are ticked; the gate runs the suite. Commit `dd0fdfb` on `ring-master/2026-10-29-2038-auth-extension-seams--g4`.

### Files

- `src/Contracts/SignInRequirement.php` (new): `for(string $email): ?ExternalSignIn`.
- `src/Data/ExternalSignIn.php` (new): a `final readonly` value with `key`, `label` and `url` (strings) and `?Model $record = null`.
- `src/Roster.php`:
  - `requireSignIn(SignInRequirement|class-string<SignInRequirement>)` registers a requirement.
  - `externalSignInFor(string $email): ?ExternalSignIn` returns `null` when nothing is registered. Otherwise it returns `null` for a super-admin (looked up by email) before any requirement is resolved or asked. Then it asks the requirements in registration order and returns the first non-null answer. It catches nothing (D-007).
  - `provisionedStatus(OrganizationModel): AccountStatus` handles three cases:
    - `null` gives `Statuses::users()::active()`, with no report (D-018).
    - A value of the configured user-status enum gives that case.
    - Anything else gives `active()`, and `report()` gets an `InvalidArgumentException`: `Organization [{slug}] has an invalid provisioned_status [{value}]; its provisioned users start as [{active}].` (D-011)
  - `ssoRequiredFor()` and the `SsoConnectionModel` import are gone (D-004).
- `src/Domains/Sso/Services/SsoSignInRequirement.php` (new): the enforcement query that was in `ssoRequiredFor()`. It answers with `key` = connection slug, `label` = organization name, `url` = `roster.sso.start` (or `''` without the route), and `record` = the connection.
- `src/Domains/Sso/SsoServiceProvider.php`: `boot()` calls `Roster::requireSignIn(SsoSignInRequirement::class)` before the availability check. That matches the old behaviour, where enforcement did not depend on the routes.
- `src/Domains/Sso/Support/NotSsoEnforced.php`: uses `externalSignInFor()` and fails with the same `sso_required` message (`organization` = label, `url` = url).
- `src/Domains/Sso/Actions/SsoLoginAction.php` and `src/Domains/Scim/Services/ScimUsers.php`: the new user's status is `app(Roster::class)->provisionedStatus($organization)`.
- Outside the group's file list:
  - `src/Support/SignInRequirements.php` (new, `@internal`): holds the registrations. Class-strings are checked when registered: anything that is not a `SignInRequirement` throws `InvalidArgumentException` naming the class. They are resolved from the container on first use, once. Registrations are keyed by class-string or object id, so the same class or instance registered again is ignored.
  - `src/RosterServiceProvider.php`: binds `SignInRequirements` as a singleton.
  - `src/Facades/Roster.php`: `@method` lines for `requireSignIn`, `externalSignInFor` and `provisionedStatus` (public-api standard).
- Tests:
  - `tests/Unit/SignInRequirementsTest.php` (new) covers:
    - the value object
    - no requirement claiming the email
    - registration order, with the first answer winning
    - a throwing requirement propagating
    - a super-admin getting `null` without the throwing requirement being asked, and getting `null` when a requirement matches
    - lazy class-string resolution, once
    - a class or an instance registered twice being asked once
    - registrations surviving `forgetScopedInstances()`
    - a non-requirement class refused, naming it
    - `method_exists(Roster::class, 'ssoRequiredFor')` being false
  - `tests/Unit/ProvisionedStatusTest.php` (new) covers:
    - `pending` and `active`
    - `null` (active, nothing reported)
    - `MemberStatus` remapping: `applied` is `pending()`
    - an invalid value: active, reported, naming `[acme]` and `[bogus]`
    - an invalid value under the remap, falling back to the remapped active
    - a saved organization
    - SSO just-in-time and SCIM `POST /Users` with a bad stored value: the user is provisioned active and the bad value is reported
  - `tests/Feature/Sso/SsoWiringTest.php` adds new tests:
    - the sign-in's key, label and url, and the unchanged message
    - connections that are not enforced or not enabled, and a value that is not an email
    - `SsoSignInRequirement` answering directly
    - a throwing requirement making `NotSsoEnforced` validation throw

### Changed existing tests (criterion 10, D-004)

- `tests/Feature/Sso/SsoWiringTest.php` "enforces SSO for the organization's domains, except for super-admins": `ssoRequiredFor(...)?->is($connection)` became `externalSignInFor(...)?->record?->is($connection)`, and the two `null` cases call `externalSignInFor()`. The assertions are otherwise the same.
- The existing SSO and SCIM "provision as pending" tests are unchanged:
  - `tests/Feature/Sso/SsoLoginActionTest.php` "keeps a just-in-time account the organization wants approved, and refuses its sign-in until then"
  - `tests/Feature/UserApprovalTest.php` "lets organizations choose whether their SCIM accounts need approval"

### `externalSignInFor()` call sites (for the PR)

- `src/Domains/Sso/Support/NotSsoEnforced.php` is the only call site in `src/`. A throwing requirement propagates out of validation to Laravel's exception handler, which reports it. The user sees the framework's generic error response (a plain 500 when `app.debug` is off), never the exception's message. When a requirement answers, the user sees only the translated `sso_required` message with the label and url.
- No other source, view or route calls it. The facade docblock lists it.

### Tests

- `pint` passes on every changed file, and `phpstan` (level 7) passes on every changed source file. The suite was not run here, as instructed; the gate runs it.

### Deviations

- Three source files outside the group's list, listed above. `Roster` is bound `scoped`, so requirements registered on it from `boot()` would be dropped by `forgetScopedInstances()` (Octane requests, queue jobs), and SSO enforcement would silently stop after the first request. The spec's Design says "Singleton registries hold code-defined state only, so they are Octane-safe", and the container-bindings standard gives registries `singleton` and keeps logic in instances. So `Roster` delegates to a singleton, and a test checks that registrations survive `forgetScopedInstances()`.
- The reported exception is a plain `InvalidArgumentException`, not a package exception subclass (errors/exceptions). Roster has no `{Package}Exception` base, and the spec uses `InvalidArgumentException` for its other registration errors.
- `tests/Unit/*` boot `TestCase` (database, container), as `tests/Pest.php` already does for `Unit`. The testing/test-layers standard wants `Unit` framework-free, but the task lists these paths.
- Left for group 5, because the files are not this group's: `README.md` line 385 still names `Roster::ssoRequiredFor($email)`, the CHANGELOG upgrade note for its removal is still to write, and the README "Extending Roster" section should say requirements must not overlap.

### Retry

This run made no code changes. The group 4 work in `dd0fdfb` was already complete, so it was checked again:

- The group's three test files (`tests/Unit/SignInRequirementsTest.php`, `tests/Unit/ProvisionedStatusTest.php` and `tests/Feature/Sso/SsoWiringTest.php`): 33 tests, all passing.
- `tests/Feature/Sso`, `tests/Feature/Scim`, `tests/Feature/UserApprovalTest.php` and `tests/ArchTest.php`: 97 of 98 pass. The one failure is `tests/Feature/Scim/ScimUsersTest.php` "provisions a new member from Okta" (line 43, `->source->value` on a string). That comes from the group 2 cast. Group 3 already fixed it under D-022, on its own branch, and the g4 branch does not have that fix.
- `pint --test` passes on every changed file.

## Group 5

Proof, documentation and gate (criteria 8, 9, 10). Tasks 5.1, 5.2 and 5.3 are ticked; the gate runs the suite, `composer test` and `composer test:browser`.

### Files

- `tests/ArchTest.php` (new tests only; no existing test changed in this group):
  - `ROSTER_CORE` lists the seven criterion 8 classes with their files. A dataset test checks each one exists, so the rules below can't pass vacuously.
  - "{class} uses no class of the SSO or SCIM domain": one `arch()` rule per class, `not->toUse()` on `Domains\Sso` and `Domains\Scim`. A single rule over the whole list would pass whenever any one class does not use the namespace (Pest's array target fails only when every target uses it). The run below shows a per-class rule failing for `DomainServiceProvider`. Pest matches targets by name prefix, so `ignoring(RosterServiceProvider)` keeps the `Roster` rule to `Roster` alone.
  - "has no MembershipSource::Scim, and nothing uses it": the case is not defined, `tryFrom('scim')` is null, and no PHP file under `src`, `database`, `resources`, `config` or `workbench` names it. Tests are left out, because `tests/Feature/Organization/MembershipSourceTest.php` holds the text as a grep sample.
  - "spells out no SSO or SCIM name in core": greps the seven files plus `src/Domains/Permission/Services/Resources.php` for `\broster\.sso\b`, `\broster\.scim\b`, `'scim'` and `"scim"`, with no exceptions. A dataset proves each pattern catches a sample line and passes look-alikes (`roster.ssohelp`, `'scimitar'`, `ScimTokenModel::class`).
  - "lists no SSO or SCIM model in the config policies": the `policies` block of `config/roster.php`, read as text, has no `sso`/`scim` word, and the loaded keys have no `\Sso\` or `\Scim\` model.
- `tests/Feature/DocsTest.php` (new tests only):
  - "documents every extension seam in the README and the Boost skill" checks the "Extending Roster" section (README `##`, Boost `### 14.`). It must contain `Resources::class`, `PermissionCatalogue::class`, `MembershipSources::class`, `SignInRequirement`, `requireSignIn(`, `externalSignInFor(`, `provisionedStatus(` and `AuditHooks`. It must also contain the in-closure `?->key !== 'roster'` check, `catch (Throwable $e)`, `report($e)`, `` `roster.*` ``, `roster.impersonation.blocked`, `roster:sync-permissions`, and the sentence "Existing built-in roles never gain a newly registered permission automatically".
  - "names only classes that exist in the Extending Roster examples": every `RefactorCircus\Roster\…` or `…\Keystone\…` name in the section's code blocks, with group imports expanded, exists (`class_exists` / `interface_exists` / `trait_exists` / `enum_exists`). The six seam classes must be among them.
  - "no longer documents Roster::ssoRequiredFor()" covers the README and the Boost skill.
  - A unit test for the two helpers, `codeBlocks()` and `refactorCircusNames()`.
- `tests/Feature/ExtendingRosterExamplesTest.php` (new):
  - Checks that the README section contains the resource and catalogue registration lines exactly as the test runs them.
  - Runs them, with fixtures in place of `WidgetModel` / `WidgetPolicy`. Then it checks:
    - the resource registers and `acme.widgets.approve` resolves through the policy (`Authorizer::check()` and `$user->can('approve', $widget)`): pass for a holder, fail without the permission, fail in another organization
    - the permissions are catalogued with their grants
    - `roster:sync-permissions` creates them as system permissions and leaves the existing `admin` role's grants unchanged, which proves the README sentence
    - a sync that creates `admin` / `member` grants them as registered
  - It is self-contained: it doesn't rely on `DocsTest.php`'s helpers, so it also runs alone.
- `README.md`:
  - New "Extending Roster" section, before "Trying it locally". It covers:
    - the `boot()`-only rule and the `roster.*` reservation
    - Resources (example plus `WidgetPolicy`; prefix rules, unregistered prefix refused, missing policy throws, `parentOf()`, late registration)
    - Permissions (catalogue example; `roster:sync-permissions` after late registration; the role-grant sentence; duplicate, reserved and unknown-role errors; the unregistered-row warning; the impersonation-blocked list staying core-only)
    - Membership sources (`sourceEnum()`, labels, raw display of unregistered values, `join()` with a string)
    - Sign-in requirements (contract example, `requireSignIn()` / `externalSignInFor()`, order, must not overlap, lazy class-strings, super-admin short-circuit, fail closed, `NotSsoEnforced` enforcing every requirement)
    - Provisioning status (`Roster::provisionedStatus()` as the provisioning API, its null and invalid fallbacks)
    - Audit context (`AuditHooks` usage, with the `Roster::auditing()` guard, the package check, try/catch with `report()`, and returning `[]`)
  - Stale text fixed:
    - "Policies" says the SSO/SCIM domains register their own policies.
    - The resources list names only the core six, plus `sso`/`scim` registered by their domains.
    - "Requiring SSO" points to `externalSignInFor()` instead of `ssoRequiredFor()`.
- `resources/boost/skills/roster-development/SKILL.md`:
  - New "14. Extending Roster" with the same content, condensed.
  - The policies line says the SSO/SCIM domains register theirs.
  - The description mentions extending Roster.
- `CHANGELOG.md` (Unreleased):
  - Breaking entries for:
    - the `Roster::ssoRequiredFor()` removal, with an upgrade note pointing to `externalSignInFor()` and its `record`
    - SSO/SCIM authorization moving into their domains (`Authorizer::RESOURCES`, `BuiltInRoles::PERMISSIONS`, `roster.policies`)
    - the `scim_token` context moving to the SCIM domain
  - An Added entry for the seams.
  - The `memberships.source` upgrade note from group 2 already met criterion 4. It names `sourceEnum()`, the silent `===`, the `UnhandledMatchError` and the raw display, so it is unchanged.

### Changed existing tests (criterion 10, for the PR)

From `git diff main...HEAD`, every existing test this job changed:

- `tests/Feature/Sso/SsoWiringTest.php` "enforces SSO for the organization's domains, except for super-admins": `ssoRequiredFor()` became `externalSignInFor()` (`?->record?->is($connection)`) (D-004).
- `tests/Feature/ContextAndDomainTest.php` "joins auto-join organizations on email verification" and "creates a personal organization when configured": `->source` became `->sourceEnum()` (D-005).
- `tests/Feature/InvitationActionsTest.php` "accepts an invitation into the organization and its teams": `->source` became `->sourceEnum()` (D-005).
- `tests/Feature/OrganizationActionsTest.php` "creates an organization with its owner as first member": `->source` became `->sourceEnum()` (D-005).
- `tests/Feature/Scim/ScimUsersTest.php` "provisions a new member from Okta": `?->source->value` became `?->source` (D-022).
- `tests/ArchTest.php` "every model has a policy in roster.policies": the input became config plus Gate-registered policies (D-020).
- `tests/Authorization/PoliciesTest.php` "registers a configurable policy for every model": the same change (D-020).

The SCIM `scim_token` audit test (`tests/Modes/Keen/KeenAuditTest.php`) did not move and is unchanged. The other `tests/ArchTest.php` and `tests/Feature/DocsTest.php` changes are new tests.

### Tests

- Run in the worktree: `tests/ArchTest.php`, `tests/Feature/DocsTest.php` and `tests/Feature/ExtendingRosterExamplesTest.php`. 56 tests, all passing.
- As a check, `DomainServiceProvider` (which imports `SsoServiceProvider`) was briefly added to `ROSTER_CORE`. Its rule failed ("not to use 'RefactorCircus\Roster\Domains\Scim'"), and the entry was then removed.
- `pint` passes on the three test files; phpstan does not analyse `tests/`.
- The full suite, `composer test` and `composer test:browser` were not run here, as instructed; the gate runs them.

### Deviations

- None from the standards.
- Left as is, because the file is not this group's: Roster's `CLAUDE.md` (Architecture, audit bullet) still says `Support\Audit` registers "the impersonator/transfer/SCIM token context". It now registers impersonator/transfer only; the SCIM domain adds `scim_token`. Group 3 noted this too.
- Uncommitted changes from the factory's group 3/4 merges (ticks in `tasks.md`, the group 3/4 sections of `build.md`) were in the worktree. They are committed with this group. The uncommitted `AGENTS.md` change is not this job's and is left out.

### Retry

The group 5 work in `0ad7a87` was already complete, so this run kept it and checked it again.

- Files: `README.md` "Provisioning status" now opens with "`Roster::provisionedStatus()` is the provisioning API", the wording criterion 7 asks for. No other change.
- Tests: `tests/ArchTest.php`, `tests/Feature/DocsTest.php` and `tests/Feature/ExtendingRosterExamplesTest.php` ran in the worktree: 56 tests, all passing, before and after the README edit. The gate runs the full suite, `composer test` and `composer test:browser`.
- Deviations: none. The changed existing tests listed above are unchanged by this run.

## Rework round 1

### Fixes

- architecture/public-api (minor): `Roster::provisionedStatus()` held the per-value resolution.
  - The resolution moved to the new `src/Support/ProvisionedStatus.php`, a `final readonly` instance. It takes the user-status enum class in its constructor and resolves with `for(OrganizationModel)`. The behaviour is unchanged: a null value gives active with no report, a valid value gives its case, and anything else gives active and is reported (D-011).
  - `Roster::provisionedStatus()` now only builds it from `Statuses::users()` and calls it.
  - The facade's `@method` signature is unchanged. Its note now says "externally provisioned accounts" and mentions the reported fallback.
- http/authorization docs (minor): the local standard and `AGENTS.md` (which `CLAUDE.md` links to) were stale. They now describe:
  - `Resources`: `Authorizer::RESOURCES` holds the core six, registered as `roster.*`. An unregistered prefix is denied. A package resource with no policy throws, and `roster.*` keeps the `permits()` fallback (D-021).
  - The SSO and SCIM providers registering their resources, permissions and Gate policies themselves. `roster.policies` lists only core models.
  - The memo resetting on `Resources::generation()`.
  - The SCIM `AuditHooks` closure, which checks the `roster` package itself, catches and reports, and returns `[]`. `Support\Audit` keeps only impersonator/transfer.
  - The arch test's policy check.
  - `AGENTS.md` also lists `Support\SignInRequirements` and `Support\ProvisionedStatus` among the cross-domain `Support/` classes.
  - Cheap stale-comment fixes in the same vein: `RosterServiceProvider::boot()` (the `registerPolicies()` comment) and the `Support\Policies\Policy` docblock.
- testing/test-layers (minor): `tests/Unit/ProvisionedStatusTest.php` keeps only the accessor cases. They now call `new ProvisionedStatus(...)` directly, with no database:
  - pending/active
  - null
  - resolving against a given enum
  - an invalid value, reported
  - two cases through `Roster::provisionedStatus()` that prove it reads `roster.statuses.users` (the remap and the remapped fallback)

  The database-backed cases moved unchanged to `tests/Feature/ProvisionedStatusTest.php`: a saved organization, SSO just-in-time with a bad value, and SCIM `POST /Users` with a bad value.
- Nit (the `Roster::__construct` signature): no change needed.

### Tests

- `composer test:unit`: 1389 tests, all passing.
- `composer analyse`: no errors.
- `pint --test` passes on every changed PHP file.

### Deviations

- None new. `tests/Unit` still boots `TestCase` through `tests/Pest.php`, as group 4 noted. The unit cases no longer use the database or a model's persistence.
- `AGENTS.md` has an uncommitted ringside-sync line ("Keep this checkout on `main`...") that is not this job's. It stays out of the commit, as in group 5.
- `src/RosterServiceProvider.php` and `src/Support/Policies/Policy.php` changed only in comments.
