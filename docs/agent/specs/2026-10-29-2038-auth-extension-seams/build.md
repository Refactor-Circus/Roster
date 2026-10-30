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
