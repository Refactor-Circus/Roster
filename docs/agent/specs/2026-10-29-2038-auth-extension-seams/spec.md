# Auth extension seams

## Goal

Another package (Guardian, first) can add authorization resources, permissions, membership sources, audit context, sign-in requirements and provisioning defaults to Roster through public registration points, so Roster names none of them. That is five registration seams (criteria 1 and 3 are two registries, `Resources` and `PermissionCatalogue`, under the authorization seam; audit context is a sixth registry only if it lands in Roster, see Risks) plus one plain accessor (`Roster::provisionedStatus()`, criterion 7), which is not a registry. Roster's own SSO and SCIM domains move onto these seams first, with no change in behaviour.

## Acceptance criteria

### Authorization resources and permissions

1. `RefactorCircus\Roster\Domains\Permission\Services\Resources` is bound as a singleton.
   - `resource(string $prefix, class-string<Model> $model)` registers the model whose policy answers permissions named `{prefix}.{verb}`. Example: `resource('roster.sso', SsoConnectionModel::class)` answers `roster.sso.manage` through `SsoConnectionPolicy::manage()`.
   - The prefix must match `^[a-z0-9-]+\.[a-z0-9-]+$`. Registering an invalid prefix, or a prefix twice, throws `InvalidArgumentException` naming it.
   - `Authorizer::RESOURCES` keeps only Roster's core resources (`users`, `organizations`, `members`, `teams`, `invitations`, `roles`), registered through the same service under `roster.*`.
   - `Authorizer::resolvePolicy()` looks up the registered prefix: everything before the last dot.
   - A unit test registers `acme.widgets` with a fake policy and asserts that `acme.widgets.approve` reaches its `approve()`.
   - A test asserts that an unregistered prefix answers `null` (no policy), as unknown `roster.*` resources do today. What the caller does with `null` (deny) is covered by the policy-gap criterion below.
   - Prefix dataset test: valid `a.b`, `roster.sso`, `a-1.b-2`; invalid empty, `a`, `a.b.c`, `A.b`, `a..b`, `a.b.`. Each invalid case, the duplicate prefix and the duplicate-of-core prefix assert that the `InvalidArgumentException` message contains the offending prefix.
   - A three-segment permission name (`acme.widgets.approve.all`) resolves the prefix `acme.widgets.approve`, finds nothing and answers `null`; a test pins it.
2. Parent lookup for registered resource models works as for core ones (`Authorizer::parentOf()`): organization-scoped checks on a registered model whose record has an organization relation pass and fail exactly as they do for `roster.sso` today. The existing SSO/SCIM authorization tests pass unchanged.
   - `parentOf()` resolves a registered model the way it does core ones: through the record's `organization_id` / `team_id` attributes or its `organization()` / `team()` relation, whichever the model has. The spec's fake-model test uses a model with an `organization()` relation and asserts: pass for a member holding the permission, fail for a member without it, and fail for a holder in another organization.
   - Policy gap: a registered prefix whose model has no resolvable Gate policy denies the check and is reported (`report()`, asserted with `Exceptions::fake()`). An unregistered prefix (`null`) also denies. Neither ever allows. Whether the gap is also caught at registration time is an open question (Risks).
   - Late registration: registering a resource after the first `Authorizer::check()` in a request invalidates the request memo (the registry bumps `generation()`), so the next check sees it. A test checks, registers, then checks again.
   - `ScreenAccess` and the `@rosterCan` directive take registered permissions the same way as core ones: a test registers `acme.widgets` with a fake policy and asserts both answer for `acme.widgets.approve`. The `sso` / `scim` entries in `config/roster.php` `policies` and `Authorizer::RESOURCES` move out of the core lists: the SSO and SCIM providers register their models' policies themselves (Gate `policy()` through the same registration), and `config/roster.php` `policies` keeps only core models. Config for a registered model's policy is the registrant's concern. `tests/ArchTest.php` `modelsWithoutPolicies()` still passes for the SSO and SCIM models.
3. `RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue` is bound as a singleton.
   - `permission(string $name, string $description, array $grantTo = [])` adds a permission. `$grantTo` lists built-in role slugs (`admin`, `member`, `lead`).
   - `BuiltInRoles::PERMISSIONS` drops `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage`. The SSO and SCIM domains register them through the catalogue, with `admin` grants, from their providers' `boot()`.
   - `BuiltInRoles::sync()`, `roster:sync-permissions` and the role migration create registered permissions too, and grant them to the named built-in roles only when sync creates that role. Grants an app has changed are never altered, as today.
   - Test: after `sync()`, the three SSO/SCIM permissions exist with `system = true`, a freshly created `admin` holds them, and an `admin` the app edited before sync keeps its edited grants.
   - Registering a name twice, or with a role slug that doesn't exist, throws `InvalidArgumentException` whose message names the offending value (tests assert it).
   - Upgrade: `sync()` on an install that already has the three rows leaves the rows and every role grant unchanged (test). `roster:sync-permissions` and the role migration are tested too: one test registers a permission after the migration ran, runs `roster:sync-permissions` and finds it created with `system = true`; another asserts the migration path creates registered permissions that exist at migration time. SSO and SCIM providers register in `boot()`, before either command body runs. `sync()` reports (does not delete) system permissions in the database that nothing registers, as a console warning from the command.
   - The README says that existing built-in roles never gain a newly registered permission automatically (grants apply only to roles the sync creates), so apps grant it by hand.

### Membership sources

4. `MembershipSource` stops being a closed set.
   - `memberships.source` is cast to string.
   - `RefactorCircus\Roster\Domains\Organization\Services\MembershipSources` is a singleton holding the built-in sources (`direct`, `invitation`, `domain`, `personal`, `import`) plus registered ones: `register(string $source, string $label)`.
   - The `MembershipSource` enum loses `Scim` and stays the list of built-ins. The SCIM domain registers `scim` with label "SCIM".
   - `MemberResource` (and so the member list data and the MCP member tools) shows the registered label. A source with no registration shows the stored value as-is and never errors (stored-identifiers standard). No Atrium screen reads `memberships.source` today (checked: nothing under `src/Atrium/` references it), so this spec changes no Atrium file.
   - No validation rule accepts a membership source today (checked: `source` appears only in organization-link and sync inputs, which are external-system names, not membership sources). Nothing to change there.
   - `MemberResource::schema()` builds `source` as a plain string, not an enum of `MembershipSource::cases()`, so registered values such as `scim` and unregistered stored values pass the MCP output schema.
   - `ManagesMemberships::join()` takes `MembershipSource|string $source` and validates a string against `MembershipSources` (built-in or registered; anything else throws `InvalidArgumentException` naming it). `ScimUsers` (both call sites) passes `'scim'`, and the `MembershipFactory` and `MembersPlanner` call sites keep passing the enum case (`->value` is stored).
   - Tests:
     - a membership stored as `scim` before this change reads back as `scim` and shows "SCIM"
     - an unregistered stored value renders and serializes unchanged, and passes the `MemberResource` output schema through `mcpTool()`, as does a registered one
     - registering a built-in value throws, message naming it
     - `join()` rejects an unknown string and accepts a registered one
     - a feature test lists members and asserts `MemberResource` shows the registered label
   - Existing assertions that compare `->source` to an enum case change with the cast. The diff to existing tests is exactly these assertions: `tests/Feature/ContextAndDomainTest.php` (lines 77, 93: `Domain`, `Personal`), `tests/Feature/InvitationActionsTest.php` (line 69: `Invitation`) and `tests/Feature/OrganizationActionsTest.php` (line 38: `Direct`), each changed to the case's `->value` (or to the enum-returning accessor if the open question settles on keeping one).

### Audit context

5. `Roster\Support\Audit::context(string $key, Closure(): ?array $contributor)` adds a context contributor. The closure runs once per audit entry, and a non-null result is stored under `$key`.
   - Impersonation (`impersonator`) and transfers (`transfer`) stay built-in.
   - SCIM moves to a contributor registered by the SCIM domain under `scim_token`. The stored shape is unchanged (`{id, name}`).
   - Registering a built-in key, or a key twice, throws `InvalidArgumentException` naming the key. Registration validates keys whether or not `Roster::auditing()` is true; contributors only run while Roster audits.
   - The closure's result is not stored between entries: it runs once per entry and is dropped, so nothing leaks across Octane requests or queue jobs.
   - Placement relative to Keystone's `AuditHooks` (this seam replaces, wraps or sits beside it) is an open question (Risks); the criterion is worded for any placement and the registration moment is the same one `Support\Audit` uses for the existing hooks, after every provider has booted. The contributor list is container-held (not a static property), so each test application starts clean.
   - A contributor that throws is reported with `report($e)` and the entry is written without that key.
   - Tests (Keen installed and `roster.audit.enabled` on, as the existing audit suite sets up): an entry written during a SCIM request carries `scim_token` exactly as before (the existing test passes unchanged); a throwing contributor doesn't stop the entry and the exception is asserted with `Exceptions::fake()`; a counter closure registered under a key runs once per entry and the stored value under the key is its result.

### Sign-in requirements

6. Contract `RefactorCircus\Roster\Contracts\SignInRequirement` with `for(string $email): ?ExternalSignIn`.
   - `ExternalSignIn` is a final readonly value with `key` (string), `label` (string) and `url` (string, where to start the sign-in), plus the underlying `?Model record` (defaults to `null` when the requirement has no model).
   - `Roster::requireSignIn(SignInRequirement|class-string<SignInRequirement>)` registers one, from a provider's `boot()`. Class-strings are resolved from the container lazily, once, on the first `externalSignInFor()`. Registering the same class or instance twice is ignored. Order is registration order, which follows provider boot order; the README says requirements must not overlap.
   - `Roster::externalSignInFor(string $email): ?ExternalSignIn` asks them in registration order and returns the first non-null answer. Super-admins always get `null`, as today. The exemption lives in Roster, not in each requirement.
   - Failure mode when a registered requirement throws: an open question (Risks). Whichever way it settles, a test asserts it, and `report()` is called either way.
   - SSO enforcement becomes an `SsoSignInRequirement` in the SSO domain, registered from its provider.
   - `NotSsoEnforced` uses `externalSignInFor()`. Whether `Roster::ssoRequiredFor()` is removed or kept as a deprecated wrapper is an open question (Risks); a CHANGELOG upgrade note covers either.
   - Tests: `SsoWiringTest`'s three enforcement cases pass when rewritten against `externalSignInFor()`; two fake requirements are asked in order, the first answer winning; a requirement registered by class-string is resolved from the container; a super-admin gets `null` even when a requirement matches; registering a class twice asks it once; the outcome of `ssoRequiredFor()` (removed: `method_exists(Roster::class, 'ssoRequiredFor')` is false; or wrapper: it delegates) is asserted per the settled answer.

### Provisioning status

7. `Roster::provisionedStatus(OrganizationModel $organization): AccountStatus` returns the status that externally provisioned users start with: the organization's `provisioned_status`, resolved through `Statuses::user()`.
   - `SsoLoginAction` and `ScimUsers` use it instead of reading the attribute.
   - If the stored value is not a valid user status (stored-identifiers standard), it falls back to `active` rather than throwing, so provisioning never fails on a bad value.
   - It is documented in the README as the provisioning API.
   - Tests: an organization with `provisioned_status = pending` provisions an SSO user and a SCIM user as pending (the existing tests pass unchanged); a unit test calls `Roster::provisionedStatus($org)` for the pending and default cases, with a remapped `Statuses::user()` entry, and for an invalid stored value (`active`).

### Proof

8. The SSO and SCIM domains reference none of the hard-coded spots this spec removes. An arch test asserts that `Authorizer`, `BuiltInRoles`, `Audit`, `Roster`, `ManagesMemberships`, `MemberResource` and the `MembershipSource` enum don't use any class under `RefactorCircus\Roster\Domains\Sso` or `...\Scim`, and that nothing outside those two domains calls `MembershipSource::Scim` (the case no longer exists). A second test greps the same core files for the literals `roster.sso`, `roster.scim` and `scim`, and `config/roster.php` `policies` for `sso` / `scim` keys.
9. The README has an "Extending Roster" section covering the seams, each with an example: resources, permissions (catalogue), membership sources, audit context, sign-in requirements, plus the `provisionedStatus()` accessor. The Boost skill has the same. A docs test checks for the exact strings `PermissionCatalogue::class`, `Resources::class`, `MembershipSources::class`, `SignInRequirement` and `Audit::context(` inside that section, and that every `Roster\...` class name used in the section's snippets exists (`class_exists` / `interface_exists`).
10. `composer test` and `composer test:browser` pass. Existing tests change only where this spec says so: the rename in criterion 6, and the `->source` assertions listed in criterion 4 (those four lines in three files). Any other change to an existing test is a failure of this criterion.

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
- Renaming permissions to `guardian.*`: Guardian. The `roster.sso.*` and `roster.scim.*` permission names stay exactly as they are.
- Atrium UI changes: `adopt-atrium-screen-registry`. No Atrium file changes in this spec.
- The SSO/SCIM MCP tools on `RosterServer`: they have no seam here and stay registered where they are.
- A general plugin system beyond these seams.

## Risks and open questions

- Changing the `memberships.source` cast from an enum to a string breaks all app code that compares `->source` to any `MembershipSource` case (not only `Scim`, which is removed), and the four existing assertions listed in criterion 4. The CHANGELOG note covers it. Open: whether to keep an enum-returning accessor or compatibility for built-in values.
- Open: `Roster::ssoRequiredFor()` is removed outright or kept one release as a deprecated wrapper.
- Open: when a registered `SignInRequirement` throws, `externalSignInFor()` fails closed (login refused) or open (requirement skipped; SSO enforcement silently off).
- Open: a registered resource whose model has no Gate policy is a registration-time error or a runtime deny (criterion 2 requires deny at runtime either way).
- Open: where the audit-context seam lives. CLAUDE.md says Roster has no audit code beyond its Keen integration and that audit APIs are never added to Roster, and today's SCIM/impersonator/transfer context is registered on Keystone's `AuditHooks` by `Support\Audit`. The seam may replace, wrap or sit beside that registration, or move to Keen.
- Open: the request's "per row support", the `guardian.*` rename and "guardian only suggests it" are not covered by this spec.
- The permission catalogue and role grants run inside the role-tables migration. A package that registers after that migration ran needs `roster:sync-permissions`, and the README says so.

## Addendum (2026-10-29): spec review decisions D-004 to D-009

- Criterion 4: the string cast stays. Add `MembershipModel::sourceEnum(): ?MembershipSource` (D-005), tested for a built-in value (returns the enum case), `scim` (null) and an unregistered value (null).
- Criterion 5 is replaced (D-006). There is no `Audit::context()`. The SCIM domain registers its `scim_token` context directly on Keystone's `AuditHooks`, filtered to `roster` entries. A contributor that throws goes to `report()`, and the entry is written without that key (D-009). The README "Extending Roster" section documents AuditHooks, not a Roster API, and the docs-test string `Audit::context(` becomes `AuditHooks`.
- Criterion 6: `ssoRequiredFor()` is removed (D-004). A throwing requirement propagates (fail closed, D-007), tested.
- Criterion 1: a registered resource whose model has no Gate policy throws `InvalidArgumentException` naming it, the first time a permission resolves to it (D-008), tested.
- The Atrium suite principle applies (Atrium D-017/D-018): these seams are what lets Guardian merely suggest Roster.
