# Auth extension seams

## Goal

Another package (Guardian, first) can add authorization resources, permissions, membership sources, audit context, sign-in requirements and provisioning defaults to Roster through public registration points, so Roster names none of them. Roster's own SSO and SCIM domains move onto these seams first, with no change in behaviour.

## Acceptance criteria

### Authorization resources and permissions

1. `RefactorCircus\Roster\Domains\Permission\Services\Resources` is bound as a singleton.
   - `resource(string $prefix, class-string<Model> $model)` registers the model whose policy answers permissions named `{prefix}.{verb}`. Example: `resource('roster.sso', SsoConnectionModel::class)` answers `roster.sso.manage` through `SsoConnectionPolicy::manage()`.
   - The prefix must match `^[a-z0-9-]+\.[a-z0-9-]+$`. Registering an invalid prefix, or a prefix twice, throws `InvalidArgumentException` naming it.
   - `Authorizer::RESOURCES` keeps only Roster's core resources (`users`, `organizations`, `members`, `teams`, `invitations`, `roles`), registered through the same service under `roster.*`.
   - `Authorizer::resolvePolicy()` looks up the registered prefix: everything before the last dot.
   - A unit test registers `acme.widgets` with a fake policy and asserts that `acme.widgets.approve` reaches its `approve()`.
   - A test asserts that an unregistered prefix answers `null` (no policy), as unknown `roster.*` resources do today.
2. Parent lookup for registered resource models works as for core ones (`Authorizer::parentOf()`): organization-scoped checks on a registered model whose record has an organization relation pass and fail exactly as they do for `roster.sso` today. The existing SSO/SCIM authorization tests pass unchanged.
3. `RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue` is bound as a singleton.
   - `permission(string $name, string $description, array $grantTo = [])` adds a permission. `$grantTo` lists built-in role slugs (`admin`, `member`, `lead`).
   - `BuiltInRoles::PERMISSIONS` drops `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage`. The SSO and SCIM domains register them through the catalogue, with `admin` grants, from their providers' `boot()`.
   - `BuiltInRoles::sync()`, `roster:sync-permissions` and the role migration create registered permissions too, and grant them to the named built-in roles only when sync creates that role. Grants an app has changed are never altered, as today.
   - Test: after `sync()`, the three SSO/SCIM permissions exist with `system = true`, a freshly created `admin` holds them, and an `admin` the app edited before sync keeps its edited grants.
   - Registering a name twice, or with a role slug that doesn't exist, throws `InvalidArgumentException`.

### Membership sources

4. `MembershipSource` stops being a closed set.
   - `memberships.source` is cast to string.
   - `RefactorCircus\Roster\Domains\Organization\Services\MembershipSources` is a singleton holding the built-in sources (`direct`, `invitation`, `domain`, `personal`, `import`) plus registered ones: `register(string $source, string $label)`.
   - The `MembershipSource` enum loses `Scim` and stays the list of built-ins. The SCIM domain registers `scim` with label "SCIM".
   - `MemberResource`, the member list and the Atrium member table show the registered label. A source with no registration shows the stored value as-is and never errors (stored-identifiers standard).
   - Validation that accepts a source (if any; spec review confirms) accepts registered values.
   - Tests:
     - a membership stored as `scim` before this change reads back as `scim` and shows "SCIM"
     - an unregistered stored value renders and serializes unchanged
     - registering a built-in value throws

### Audit context

5. `Roster\Support\Audit::context(string $key, Closure(): ?array $contributor)` adds a context contributor. The closure runs once per audit entry, and a non-null result is stored under `$key`.
   - Impersonation (`impersonator`) and transfers (`transfer`) stay built-in.
   - SCIM moves to a contributor registered by the SCIM domain under `scim_token`. The stored shape is unchanged (`{id, name}`).
   - Registering a built-in key, or a key twice, throws.
   - A contributor that throws is reported, and the entry is written without that key.
   - Tests: an entry written during a SCIM request carries `scim_token` exactly as before (the existing test passes unchanged), and a throwing contributor doesn't stop the entry.

### Sign-in requirements

6. Contract `RefactorCircus\Roster\Contracts\SignInRequirement` with `for(string $email): ?ExternalSignIn`.
   - `ExternalSignIn` is a final readonly value with `key` (string), `label` (string) and `url` (string, where to start the sign-in), plus the underlying `?Model record`.
   - `Roster::requireSignIn(SignInRequirement|class-string<SignInRequirement>)` registers one.
   - `Roster::externalSignInFor(string $email): ?ExternalSignIn` asks them in registration order and returns the first non-null answer. Super-admins always get `null`, as today. The exemption lives in Roster, not in each requirement.
   - SSO enforcement becomes an `SsoSignInRequirement` in the SSO domain, registered from its provider.
   - `Roster::ssoRequiredFor()` is removed, and `NotSsoEnforced` uses `externalSignInFor()`. A CHANGELOG upgrade note covers this.
   - Tests: `SsoWiringTest`'s three enforcement cases pass when rewritten against `externalSignInFor()`, and two fake requirements are asked in order, the first answer winning.

### Provisioning status

7. `Roster::provisionedStatus(OrganizationModel $organization): AccountStatus` returns the status that externally provisioned users start with: the organization's `provisioned_status`, resolved through `Statuses::user()`.
   - `SsoLoginAction` and `ScimUsers` use it instead of reading the attribute.
   - It is documented in the README as the provisioning API.
   - Test: an organization with `provisioned_status = pending` provisions an SSO user and a SCIM user as pending (the existing tests pass unchanged).

### Proof

8. The SSO and SCIM domains reference none of the hard-coded spots this spec removes. An arch test asserts that `Authorizer`, `BuiltInRoles`, `Audit`, `Roster` and the `MembershipSource` enum don't use any class under `RefactorCircus\Roster\Domains\Sso` or `...\Scim`.
9. The README has an "Extending Roster" section covering the five seams, each with an example. The Boost skill has the same. A docs test checks for the exact strings `PermissionCatalogue::class`, `Resources::class`, `MembershipSources::class`, `SignInRequirement` and `Audit::context(` inside that section.
10. `composer test` and `composer test:browser` pass. The existing SSO, SCIM, permission, audit and member suites need no assertion changes beyond criterion 6's rename.

## Design

- **`Domains/Permission/Services/Resources`:** registry `prefix => model`. `Authorizer` reads it, and the core six resources are registered by `PermissionServiceProvider::register()` so they exist before any `boot()`.
- **`Domains/Permission/Services/PermissionCatalogue`:** registry `name => [description, grantTo]`. `BuiltInRoles::sync()` merges `PERMISSIONS` with the catalogue.
- **`Domains/Organization/Services/MembershipSources`:** labels by value. The enum stays for built-ins, and the `source` cast changes from the enum to string.
- **`Support\Audit::context()`:** static registration onto a singleton contributor list, memoized per entry.
- **`Contracts\SignInRequirement`, `Data\ExternalSignIn`, `Roster::requireSignIn()` / `externalSignInFor()`:** the SSO enforcement query moves into `Domains/Sso/Services/SsoSignInRequirement`.
- **`Roster::provisionedStatus()`.**

All registrations happen from a provider's `boot()`, except the core resources. Singleton registries hold code-defined state only, so they are Octane-safe.

## Out of scope

- Moving SSO/SCIM out of Roster: Guardian and `remove-sso-and-scim`.
- Renaming permissions to `guardian.*`: Guardian.
- Atrium UI changes: `adopt-atrium-screen-registry`.
- A general plugin system beyond these five seams.

## Risks and open questions

- Changing the `memberships.source` cast from an enum to a string is a breaking change for app code comparing against `MembershipSource::Scim`. The CHANGELOG note covers it. The other enum cases stay usable through `->value` comparisons, and spec review decides whether to keep an enum-returning helper.
- `ssoRequiredFor()` removal is breaking. Alternatively, it could be kept one release as a deprecated wrapper.
- The permission catalogue and role grants run inside the role-tables migration. A package that registers after that migration ran needs `roster:sync-permissions`, and the README says so.
