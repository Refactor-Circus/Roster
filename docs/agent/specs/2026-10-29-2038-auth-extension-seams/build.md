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
