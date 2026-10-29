# Auth extension seams

## Goal

Another package (Guardian, first) can add authorization resources, permissions, membership sources, sign-in requirements and provisioning defaults to Roster through public registration points, so Roster names none of them. That is four Roster registries (`Resources` and `PermissionCatalogue` under the authorization seam, `MembershipSources`, and the `SignInRequirement` contract registered through `Roster::requireSignIn()` and asked through `Roster::externalSignInFor()`) plus one plain accessor (`Roster::provisionedStatus()`, criterion 7), which is not a registry. `roster.*` resource and permission prefixes stay reserved for Roster's own domains (D-010).

Audit context is not a Roster seam (D-006). Roster adds no audit API: the in-tree SCIM domain registers its `scim_token` context directly on Keystone's `AuditHooks`, wrapping its own closure in a try/catch that calls `report()` and contributes nothing (D-009, D-012). Roster's own SSO and SCIM domains move onto these seams first, with no change in behaviour.

## Acceptance criteria

### Authorization resources and permissions

1. `RefactorCircus\Roster\Domains\Permission\Services\Resources` is bound as a singleton.
   - `resource(string $prefix, class-string<Model> $model)` registers the model whose policy answers permissions named `{prefix}.{verb}`. Example: `resource('acme.widgets', WidgetModel::class)` answers `acme.widgets.approve` through `WidgetPolicy::approve()`.
   - The prefix must match `^[a-z0-9-]+\.[a-z0-9-]+$`. Registering an invalid prefix, or a prefix twice, throws `InvalidArgumentException` naming it.
   - `roster.*` is reserved (D-010). `resource()` throws `InvalidArgumentException` naming the prefix for any `roster.*` prefix. Roster's own domains (the six core resources and the in-tree `roster.sso` / `roster.scim`) register through an `@internal` method on the same service that accepts only `roster.*`.
   - `Authorizer::RESOURCES` keeps only Roster's core resources (`users`, `organizations`, `members`, `teams`, `invitations`, `roles`), registered through the same service under `roster.*`.
   - `Authorizer::resolvePolicy()` looks up the registered prefix: everything before the last dot. An unregistered prefix answers `null` (no policy, so deny), as unknown `roster.*` resources do today.
   - Missing policy (D-008, D-013): a registered prefix whose model has no resolvable Gate policy makes `Authorizer::resolvePolicy()` throw `InvalidArgumentException` naming the resource, the first time a permission resolves to it. The exception is never converted to an allow, and the request memo stores nothing for a check that threw.
   - Tests:
     - `acme.widgets` with a fake policy: `acme.widgets.approve` reaches its `approve()`
     - an unregistered prefix answers `null`
     - prefix dataset: valid `a.b`, `a-1.b-2`; invalid empty, `a`, `a.b.c`, `A.b`, `a..b`, `a.b.`; each invalid case, a duplicate, a duplicate of a core prefix and `roster.anything` through `resource()` assert the message contains the prefix
     - a three-segment name (`acme.widgets.approve.all`) resolves the prefix `acme.widgets.approve`, finds nothing and answers `null`
     - a registered model with no policy throws from `Authorizer::check()`, `ScreenAccess` and `@rosterCan`, message naming the resource (`assertThrows`, no `report()`)
2. Registered resources behave like core ones.
   - `Authorizer::parentOf()` resolves a registered model through the record's `organization_id` / `team_id` attributes or its `organization()` / `team()` relation, whichever it has. A fake-model test with an `organization()` relation asserts: pass for a member holding the permission, fail for a member without it, fail for a holder in another organization. The existing SSO/SCIM authorization tests pass unchanged.
   - Late registration: `Resources` keeps its own `generation()` counter, bumped on every registration, and `Authorizer`'s memo keys on it together with `Permissions::generation()`. A test checks (denied, unregistered prefix), registers the resource, checks again and sees the answer.
   - `ScreenAccess` and `@rosterCan` take registered permissions the same way as core ones (test with `acme.widgets.approve`).
   - The impersonation-blocked list stays core-only, as documented (D-010): `roster.impersonation.blocked` is config, registered permissions are not added to it, and apps add patterns there themselves. The README says so.
   - The `sso` / `scim` entries leave `config/roster.php` `policies` and `Authorizer::RESOURCES`: the SSO and SCIM providers register their models' policies themselves (Gate `policy()`), and `config/roster.php` `policies` keeps only core models. `tests/ArchTest.php` `modelsWithoutPolicies()` still passes for the SSO and SCIM models.
3. `RefactorCircus\Roster\Domains\Permission\Services\PermissionCatalogue` is bound as a singleton.
   - `permission(string $name, string $description, array $grantTo = [])` adds a permission. `$grantTo` lists built-in role slugs (`admin`, `member`, `lead`). As with resources, `roster.*` names are reserved (D-010): `permission()` throws for them, and Roster's own domains use the `@internal` counterpart.
   - `BuiltInRoles::PERMISSIONS` drops `roster.sso.view`, `roster.sso.manage` and `roster.scim.manage`. The SSO and SCIM domains register them through the catalogue, with `admin` grants, from their providers' `boot()`.
   - `BuiltInRoles::sync()`, `roster:sync-permissions` and the role migration create registered permissions too, and grant them to the named built-in roles only when sync creates that role. Grants an app has changed are never altered, as today. `sync()` reports (does not delete) system permissions in the database that nothing registers, as a console warning from the command.
   - Registering a name twice, a `roster.*` name through `permission()`, or a role slug that doesn't exist throws `InvalidArgumentException` whose message names the offending value.
   - Tests:
     - after `sync()`, the three SSO/SCIM permissions exist with `system = true`, a freshly created `admin` holds them, and an `admin` the app edited before sync keeps its edited grants
     - an unregistered system permission row is reported by `roster:sync-permissions` (`expectsOutputToContain`) and still exists
     - the role-tables migration through `migrate` on a fresh database with the domain providers registered creates the three permissions and the `admin` grants; a fake permission registered before the migration is created too
     - `sync()` on an install that already has the three rows leaves the rows and every grant unchanged
     - a permission registered after the migration ran is created with `system = true` by `roster:sync-permissions`
     - each invalid registration above throws with the value in the message
   - The README says that existing built-in roles never gain a newly registered permission automatically (grants apply only to roles the sync creates), so apps grant it by hand.

### Membership sources

4. `MembershipSource` stops being a closed set (D-005).
   - `memberships.source` is cast to string. `MembershipModel::sourceEnum(): ?MembershipSource` returns the enum case for a built-in value and `null` for `scim` or any other non-built-in value.
   - `RefactorCircus\Roster\Domains\Organization\Services\MembershipSources` is a singleton holding the built-in sources (`direct`, `invitation`, `domain`, `personal`, `import`) plus registered ones: `register(string $source, string $label)`.
   - The `MembershipSource` enum loses `Scim` and stays the list of built-ins. The SCIM domain registers `scim` with label "SCIM".
   - `MemberResource` (and so the member list data and the MCP member tools) shows the registered label. A source with no registration shows the stored value as-is and never errors (stored-identifiers standard). `MemberResource::schema()` builds `source` as a plain string. No Atrium screen and no validation rule reads a membership source today, so neither changes.
   - `ManagesMemberships::join()` takes `MembershipSource|string $source` and validates a string against `MembershipSources` (anything unknown throws `InvalidArgumentException` naming it). `ScimUsers` (both call sites) passes `'scim'`; `MembershipFactory` and `MembersPlanner` keep passing the enum case.
   - Tests:
     - `sourceEnum()` returns the case for a built-in value, `null` for `scim`, `null` for an unregistered value
     - registering a value twice, or a built-in value, throws, message naming it
     - a membership stored as `scim` before this change reads back as `scim` and shows "SCIM"
     - an unregistered stored value renders and serializes unchanged and passes the `MemberResource` output schema through `mcpTool()`, as does a registered one
     - `join()` rejects an unknown string and accepts a registered one
     - a feature test lists members and asserts the registered label
   - Consumers: an arch or grep test asserts no file under `src/` compares `->source` to a `MembershipSource` case, calls an enum method on it, or passes an enum to `where('source', ...)`. The implementer lists the audited places in the PR. The CHANGELOG upgrade note names `sourceEnum()` as the migration path, warns that `=== MembershipSource::X` now silently evaluates false and `match` on `->source` throws `UnhandledMatchError`, and says a stored value with no registration displays raw.

### Audit context

5. Roster adds no audit API (D-006). There is no `Audit::context()` or other Roster method for audit context.
   - `impersonator` and `transfer` stay built-in in `Support\Audit`; `scim_token` is removed from `Support\Audit::context()`.
   - The SCIM domain provider registers its own closure with `AuditHooks::context()`, at the same moment `Support\Audit::register()` registers its hooks, and only when `Roster::auditing()` is true. It returns `['scim_token' => {id, name}]` (shape unchanged) while a SCIM token is in `ScimContext`.
   - Roster entries only: `AuditHooks` has no package filter (every context closure runs for every event), so the closure filters itself, with no Keystone change. It resolves Keystone's `PackageRegistry` and returns `[]` unless `$packages->for($event)?->key === 'roster'`, the same check `Support\Audit::ownsEvent()` makes.
   - Failure (D-009, D-012): the closure wraps its body in try/catch; on a throw it calls `report($e)` and returns `[]`, so the entry is written without `scim_token`. It returns `[]`, not `null`: `contextFor()` spreads each closure's result, and spreading `null` is a `TypeError`. `AuditHooks::contextFor()` has no try/catch, so the catch lives in the closure.
   - Tests (Keen installed and `roster.audit.enabled` on, as the existing audit suite sets up): the existing SCIM `scim_token` audit test passes unchanged, or moves to the SCIM domain with the same assertions; an entry for a non-Roster event written during a SCIM request carries no `scim_token`; a throwing `ScimContext` leaves the entry written without `scim_token`, reported (`Exceptions::fake()`); with `roster.audit.enabled` off the SCIM domain registers nothing.

### Sign-in requirements

6. Contract `RefactorCircus\Roster\Contracts\SignInRequirement` with `for(string $email): ?ExternalSignIn`.
   - `ExternalSignIn` is a final readonly value with `key`, `label` and `url` (strings; `url` is where to start the sign-in), plus `?Model record` (default `null`).
   - `Roster::requireSignIn(SignInRequirement|class-string<SignInRequirement>)` registers one, from a provider's `boot()`. Class-strings are resolved from the container lazily, once, on the first `externalSignInFor()`. Registering the same class or instance twice is ignored. Order is registration order; the README says requirements must not overlap.
   - `Roster::externalSignInFor(string $email): ?ExternalSignIn` asks them in order and returns the first non-null answer. Super-admins get `null` before any requirement is invoked, so a broken requirement cannot lock one out.
   - Fail closed (D-007): `externalSignInFor()` does not catch. A throwing requirement propagates, the login or registration refused through `NotSsoEnforced` fails, and Laravel's handler reports it. The implementer lists every call site of `externalSignInFor()` and confirms none leaks exception detail to the user.
   - SSO enforcement becomes `Domains/Sso/Services/SsoSignInRequirement`, registered from the SSO provider. `NotSsoEnforced` uses `externalSignInFor()`.
   - `Roster::ssoRequiredFor()` is removed (D-004), with a CHANGELOG upgrade note pointing to `externalSignInFor()`.
   - Tests: `SsoWiringTest`'s `ssoRequiredFor` enforcement cases pass rewritten against `externalSignInFor()`; a throwing requirement propagates from `externalSignInFor()` and makes `NotSsoEnforced` validation throw rather than pass; a super-admin gets `null` without a throwing requirement being called, and `null` when a requirement matches; two fake requirements are asked in order, the first answer winning; a class-string requirement is resolved from the container; a class registered twice is asked once; `method_exists(Roster::class, 'ssoRequiredFor')` is false.

### Provisioning status

7. `Roster::provisionedStatus(OrganizationModel $organization): AccountStatus` returns the status that externally provisioned users start with: the organization's `provisioned_status`, resolved through `Statuses::user()`.
   - `SsoLoginAction` and `ScimUsers` use it instead of reading the attribute.
   - Invalid stored value (D-011): it falls back to the `Statuses::user()` resolution of `active` and calls `report()` with an exception naming the organization and the value, so provisioning never fails and the bad value is never silent. A null value resolves to `active` without reporting.
   - The README documents it as the provisioning API.
   - Tests: an organization with `provisioned_status = pending` provisions an SSO user and a SCIM user as pending (existing tests unchanged); unit tests for pending, null, a remap of `pending` to a custom value (returns the remapped status), and an invalid value (returns `active`, reported, asserted with `Exceptions::fake()`).

### Proof

8. Core references neither the SSO nor the SCIM domain. An arch test asserts that `Authorizer`, `BuiltInRoles`, `Roster`, `ManagesMemberships`, `MemberResource`, `Support\Audit` and the `MembershipSource` enum use no class under `RefactorCircus\Roster\Domains\Sso` or `...\Scim`, and that nothing calls `MembershipSource::Scim`. A second test greps exactly those files, plus `src/Domains/Permission/Services/Resources.php`, for the word-bounded patterns `roster\.sso`, `roster\.scim` and the quoted literals `'scim'` / `"scim"`, with no exceptions, and greps `config/roster.php` `policies` for `sso` / `scim` keys.
9. The README has an "Extending Roster" section with an example for each: resources, permissions (catalogue), membership sources, sign-in requirements, the `provisionedStatus()` accessor, and audit context as `AuditHooks` usage (not a Roster API), including the in-closure `roster` package check and try/catch. It also states the `roster.*` reservation. The Boost skill has the same. A docs test checks for the exact strings `PermissionCatalogue::class`, `Resources::class`, `MembershipSources::class`, `SignInRequirement` and `AuditHooks` inside that section, the sentence that existing built-in roles never gain a newly registered permission automatically, and that every `Roster\...` class name in the section's snippets exists (`class_exists` / `interface_exists`). A test runs the resource and catalogue examples' registration calls and asserts they register and resolve.
10. `composer test` and `composer test:browser` pass. Existing tests change only where this spec's decisions force it, each listed in the PR by file and test name:
    - `SsoWiringTest`: the `ssoRequiredFor` tests, rewritten against `externalSignInFor()` (D-004)
    - `ContextAndDomainTest`, `InvitationActionsTest`, `OrganizationActionsTest`: assertions comparing `->source` with a `MembershipSource` case change to `->value` or `sourceEnum()`, the implementer's choice (D-005)
    - the SCIM `scim_token` audit test, only if it moves to the SCIM domain with the same assertions (D-006)

    Any other change to an existing test fails this criterion.

## Design

- **`Domains/Permission/Services/Resources`:** registry `prefix => model` with its own `generation()`. Public `resource()` rejects `roster.*`; an `@internal` method takes Roster's own. The core six are registered by `PermissionServiceProvider::register()` so they exist before any `boot()`. `Authorizer::resolvePolicy()` reads it and throws for a registered model with no policy.
- **`Domains/Permission/Services/PermissionCatalogue`:** registry `name => [description, grantTo]`, with the same `roster.*` reservation. `BuiltInRoles::sync()` merges `PERMISSIONS` with the catalogue.
- **`Domains/Organization/Services/MembershipSources`:** labels by value. The enum stays for built-ins; the `source` cast becomes string, with `MembershipModel::sourceEnum()`.
- **`Contracts\SignInRequirement`, `Data\ExternalSignIn`, `Roster::requireSignIn()` / `externalSignInFor()`:** the SSO enforcement query moves into `Domains/Sso/Services/SsoSignInRequirement`. `ssoRequiredFor()` is gone.
- **`Roster::provisionedStatus()`:** accessor with a reported fallback to `active`.
- **Audit context:** no Roster API. The SCIM domain provider registers a self-filtering, self-catching closure on Keystone's `AuditHooks`.

All registrations happen from a provider's `boot()`, except the core resources. Singleton registries hold code-defined state only, so they are Octane-safe.

## Out of scope

- Screen slots and tab bars for Roster screens, and any Atrium UI change: the Roster spec `adopt-atrium-screen-registry` (D-014, Atrium D-013). No Atrium file changes here.
- Renaming permissions to `guardian.*`: Guardian. The `roster.sso.*` and `roster.scim.*` names stay exactly as they are.
- Moving SSO/SCIM code out of Roster: Guardian and `remove-sso-and-scim`.
- The SSO/SCIM MCP tools on `RosterServer`: no seam here; they stay registered where they are.
- A Keystone change, including a package filter or try/catch in `AuditHooks`.
- A general plugin system beyond these seams.

## Risks

- The `memberships.source` string cast breaks app code comparing `->source` to `MembershipSource` cases, silently for `===`. Criterion 4 covers the consumer audit, `sourceEnum()` and the CHANGELOG note.
- Removing `ssoRequiredFor()` breaks apps calling it. The CHANGELOG upgrade note points to `externalSignInFor()`.
- Fail-closed sign-in means a buggy registered requirement blocks every non-super-admin login until fixed; that is the intended trade-off for a security control (D-007).
- The permission catalogue and role grants run inside the role-tables migration. A package that registers after that migration ran needs `roster:sync-permissions`, and the README says so.
- When SSO/SCIM move to Guardian, their `roster.sso` / `roster.scim` registrations must be renamed there, since `roster.*` will be closed to them (D-010).

## Changelog

Spec review decisions, 2026-10-29, folded into the sections above.

- D-004: `Roster::ssoRequiredFor()` removed, with a CHANGELOG note (criteria 6, 10).
- D-005: `memberships.source` is a string cast plus `MembershipModel::sourceEnum()` (criteria 4, 10).
- D-006: audit context goes on Keystone `AuditHooks`; Roster adds no audit API (Goal, criterion 5).
- D-007: a throwing `SignInRequirement` fails closed and is reported by Laravel's handler (criterion 6).
- D-008: a registered resource with no Gate policy throws `InvalidArgumentException` naming it (criterion 1).
- D-009: a throwing audit-context contributor is reported and the entry is written without its key (criterion 5).
- D-010: `roster.*` resource and permission prefixes are reserved for Roster's own domains; the impersonation-blocked list stays core-only (criteria 1, 2, 3).
- D-011: an invalid stored `provisioned_status` falls back to `active` and calls `report()` (criterion 7).
- D-012: the SCIM closure catches its own exceptions (try/catch, `report()`, returns `[]` because `contextFor()` spreads the result); no Keystone change (criterion 5).
- D-013: the missing-policy exception is thrown from `Authorizer::resolvePolicy()` (criterion 1).
- D-014: screen slots and tab bars moved to Out of scope (`adopt-atrium-screen-registry`).
- D-015: spec rewritten in place; the addendum became this changelog.
